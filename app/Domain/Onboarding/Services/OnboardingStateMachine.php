<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Services;

use App\Domain\Chain\ChainAdapterResolver;
use App\Domain\Onboarding\Enums\OnboardingState;
use App\Exceptions\Onboarding\InvalidOnboardingTransitionException;
use App\Models\OnboardingPassphraseVerifier;
use App\Models\OnboardingSession;
use App\Models\SecurityEvent;
use App\Models\SupportedNetwork;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAddress;
use App\Services\CryptoEnvelopeValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OnboardingStateMachine
{
    public const MAX_PASSPHRASE_ATTEMPTS = 10;

    public function __construct(
        private OnboardingTokenService $tokens,
        private PassphraseVerifierService $passphrases,
        private CryptoEnvelopeValidator $crypto,
        private ChainAdapterResolver $adapters,
    ) {}

    /**
     * @return array{session: OnboardingSession, step_token: string, verifier_salt_hex: string}
     */
    public function bootstrapAfterSignup(User $user): array
    {
        $minted = $this->tokens->mint();
        $session = OnboardingSession::query()->create([
            'user_id' => $user->id,
            'state' => OnboardingState::AwaitingVaultUpload->value,
            'step_token_hash' => $minted['hash'],
            'step_token_expires_at' => $minted['expires_at'],
            'passphrase_attempts' => 0,
        ]);
        $salt = $this->passphrases->createSaltForUser($user->id);

        return [
            'session' => $session,
            'step_token' => $minted['plain'],
            'verifier_salt_hex' => $salt['salt_hex'],
        ];
    }

    public function requireValidToken(OnboardingSession $session, string $plainToken): void
    {
        if (! $this->tokens->validate($session, $plainToken)) {
            throw ValidationException::withMessages([
                'step_token' => [__('Invalid or expired onboarding step. Please refresh and try again.')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $vaultEnvelope
     * @param  list<array{chain: string, address: string, derivation_path: string}>  $addresses
     */
    public function submitVault(
        User $user,
        OnboardingSession $session,
        string $plainToken,
        array $vaultEnvelope,
        string $passphraseVerifierHmacHex,
        array $addresses,
        string $publicWalletId,
        string $vaultVersion,
    ): string {
        $this->assertSessionActive($session);
        if ($session->stateEnum() !== OnboardingState::AwaitingVaultUpload) {
            throw new InvalidOnboardingTransitionException;
        }
        $this->requireValidToken($session, $plainToken);

        $validated = $this->crypto->validateVaultEnvelope($vaultEnvelope);
        $verifier = OnboardingPassphraseVerifier::query()->where('user_id', $user->id)->firstOrFail();
        $this->passphrases->storeExpectedHmac($verifier, $passphraseVerifierHmacHex);

        $nextToken = DB::transaction(function () use ($user, $session, $validated, $vaultEnvelope, $addresses, $publicWalletId, $vaultVersion) {
            $wallet = Wallet::query()->create([
                'user_id' => $user->id,
                'label' => __('Primary'),
                'public_wallet_id' => $publicWalletId,
                'vault_version' => $vaultVersion,
                'kdf_params' => $validated['kdf_params'],
                'wallet_vault_ciphertext' => json_encode($vaultEnvelope, JSON_THROW_ON_ERROR),
                'is_active' => false,
            ]);

            foreach ($addresses as $row) {
                $network = SupportedNetwork::query()->where('chain', $row['chain'])->where('enabled', true)->first();
                if ($network === null) {
                    throw ValidationException::withMessages([
                        'addresses' => [__('Unsupported chain: :c', ['c' => $row['chain']])],
                    ]);
                }
                $adapter = $this->adapters->forNetwork($network);
                if (! $adapter->validateAddress($row['address'], $network)) {
                    throw ValidationException::withMessages([
                        'addresses' => [__('Invalid address for :c', ['c' => $row['chain']])],
                    ]);
                }
                $normalized = $adapter->normalizeAddress($row['address'], $network);
                WalletAddress::query()->create([
                    'wallet_id' => $wallet->id,
                    'supported_network_id' => $network->id,
                    'chain' => $network->chain,
                    'address' => $normalized,
                    'derivation_path' => $row['derivation_path'],
                    'derivation_index' => 0,
                    'is_change' => false,
                ]);
            }

            $session->update([
                'state' => OnboardingState::AwaitingPassphraseAck->value,
                'challenge_indices' => null,
            ]);

            SecurityEvent::query()->create([
                'user_id' => $user->id,
                'event_type' => 'onboarding_vault_stored',
                'severity' => 'info',
                'ip_address' => null,
                'metadata_json' => ['wallet_id' => $wallet->id],
                'created_at' => now(),
            ]);

            return $this->tokens->rotate($session);
        });

        return $nextToken;
    }

    public function acknowledgePassphrase(
        User $user,
        OnboardingSession $session,
        string $plainToken,
    ): string {
        $this->assertSessionActive($session);
        if ($session->stateEnum() !== OnboardingState::AwaitingPassphraseAck) {
            throw new InvalidOnboardingTransitionException;
        }
        $this->requireValidToken($session, $plainToken);

        return DB::transaction(function () use ($session) {
            $session->update([
                'state' => OnboardingState::AwaitingPassphraseConfirm->value,
                'challenge_indices' => null,
            ]);

            return $this->tokens->rotate($session);
        });
    }

    /**
     * Full mnemonic re-entry; compared to verifier HMAC from vault step (never stores plaintext phrase).
     */
    public function confirmPassphrase(
        User $user,
        OnboardingSession $session,
        string $plainToken,
        string $mnemonic,
    ): string {
        $this->assertSessionActive($session);
        if ($session->stateEnum() !== OnboardingState::AwaitingPassphraseConfirm) {
            throw new InvalidOnboardingTransitionException;
        }
        $this->requireValidToken($session, $plainToken);

        $verifier = OnboardingPassphraseVerifier::query()->where('user_id', $user->id)->firstOrFail();
        $candidate = $this->passphrases->normalizeMnemonic($mnemonic);
        $words = $candidate === '' ? [] : explode(' ', $candidate);
        if (count($words) !== 24) {
            throw ValidationException::withMessages([
                'mnemonic' => [__('Enter all 24 recovery words in order.')],
            ]);
        }

        if (! $this->passphrases->verify($verifier, $candidate)) {
            $session->increment('passphrase_attempts');
            if ($session->fresh()->passphrase_attempts >= self::MAX_PASSPHRASE_ATTEMPTS) {
                $session->update([
                    'state' => OnboardingState::LockedOut->value,
                    'locked_at' => now(),
                ]);
                SecurityEvent::query()->create([
                    'user_id' => $user->id,
                    'event_type' => 'onboarding_passphrase_locked',
                    'severity' => 'warning',
                    'ip_address' => null,
                    'metadata_json' => [],
                    'created_at' => now(),
                ]);
            }

            throw ValidationException::withMessages([
                'mnemonic' => [__('That recovery phrase does not match. Check spelling and order.')],
            ]);
        }

        return DB::transaction(function () use ($user, $session) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->where('is_active', false)
                ->orderByDesc('id')
                ->first();
            if ($wallet !== null) {
                $wallet->update(['is_active' => true]);
            }
            $user->forceFill([
                'onboarding_status' => OnboardingState::Completed->value,
                'onboarding_completed_at' => now(),
            ])->save();

            $session->update([
                'state' => OnboardingState::Completed->value,
                'challenge_indices' => null,
            ]);

            SecurityEvent::query()->create([
                'user_id' => $user->id,
                'event_type' => 'onboarding_completed',
                'severity' => 'info',
                'ip_address' => null,
                'metadata_json' => [],
                'created_at' => now(),
            ]);

            return $this->tokens->rotate($session);
        });
    }

    private function assertSessionActive(OnboardingSession $session): void
    {
        if ($session->stateEnum() === OnboardingState::LockedOut) {
            throw ValidationException::withMessages([
                'onboarding' => [__('This onboarding flow is locked. Contact support.')],
            ]);
        }
    }
}

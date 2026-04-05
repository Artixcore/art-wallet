<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Services;

use App\Models\OnboardingPassphraseVerifier;
use Illuminate\Support\Str;
use Normalizer;

final class PassphraseVerifierService
{
    /**
     * @return array{salt_hex: string, model: OnboardingPassphraseVerifier}
     */
    public function createSaltForUser(int $userId): array
    {
        $saltHex = bin2hex(random_bytes(32));
        $model = OnboardingPassphraseVerifier::query()->updateOrCreate(
            ['user_id' => $userId],
            ['verifier_salt_hex' => $saltHex, 'verifier_hmac_hex' => null],
        );

        return ['salt_hex' => $saltHex, 'model' => $model];
    }

    public function storeExpectedHmac(OnboardingPassphraseVerifier $verifier, string $hmacHex): void
    {
        if (! preg_match('/^[a-f0-9]{64}$/i', $hmacHex)) {
            throw new \InvalidArgumentException('Invalid verifier HMAC format.');
        }
        $verifier->update(['verifier_hmac_hex' => strtolower($hmacHex)]);
    }

    /**
     * BIP-39 English mnemonic normalization: NFKC, lowercase, single spaces between words.
     */
    public function normalizeMnemonic(string $phrase): string
    {
        if (! class_exists(Normalizer::class)) {
            $normalized = $phrase;
        } else {
            $normalized = Normalizer::normalize(trim($phrase), Normalizer::FORM_KC) ?? trim($phrase);
        }
        $parts = preg_split('/\s+/u', Str::lower($normalized), -1, PREG_SPLIT_NO_EMPTY);

        return $parts === false ? '' : implode(' ', $parts);
    }

    public function computeHmacHex(string $saltHex, string $normalizedMnemonic): string
    {
        $salt = hex2bin($saltHex);
        if ($salt === false || strlen($salt) !== 32) {
            throw new \InvalidArgumentException('Invalid salt.');
        }

        return hash_hmac('sha256', $normalizedMnemonic, $salt, false);
    }

    public function verify(OnboardingPassphraseVerifier $verifier, string $normalizedMnemonic): bool
    {
        $expected = $verifier->verifier_hmac_hex;
        if ($expected === null || $expected === '') {
            return false;
        }

        $actual = $this->computeHmacHex($verifier->verifier_salt_hex, $normalizedMnemonic);

        return hash_equals(strtolower($expected), $actual);
    }

    /**
     * @return list<int>
     */
    public function pickChallengeIndices(int $wordCount = 24, int $pick = 6): array
    {
        $indices = range(0, $wordCount - 1);
        shuffle($indices);

        $pick = min($pick, $wordCount);
        $selected = array_slice($indices, 0, $pick);
        sort($selected);

        return array_values($selected);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Services;

use App\Models\VerifiedWalletAddress;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

final class VerifiedWalletAddressSyncService
{
    public function __construct(
        private readonly SolanaPublicKeyValidator $solana,
    ) {}

    /**
     * Upsert verified SOL address for the wallet owner when a Solana address is synced.
     */
    public function syncSolAddressFromWallet(Wallet $wallet, string $normalizedAddress): void
    {
        $normalized = $this->solana->validateAndNormalize($normalizedAddress);

        $existing = VerifiedWalletAddress::query()
            ->where('chain', 'SOL')
            ->where('address', $normalized)
            ->first();

        if ($existing !== null && (int) $existing->user_id !== (int) $wallet->user_id) {
            Log::warning('verified_wallet_address_conflict', [
                'wallet_id' => $wallet->id,
                'existing_user_id' => $existing->user_id,
            ]);

            return;
        }

        VerifiedWalletAddress::query()->updateOrCreate(
            [
                'chain' => 'SOL',
                'address' => $normalized,
            ],
            [
                'user_id' => $wallet->user_id,
                'verified_at' => now(),
                'verification_source' => 'wallet_sync',
            ],
        );
    }
}

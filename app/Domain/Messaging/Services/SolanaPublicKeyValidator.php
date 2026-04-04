<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Services;

use App\Domain\Messaging\Support\Base58;
use InvalidArgumentException;

final class SolanaPublicKeyValidator
{
    /**
     * Trim and validate a Solana address (ed25519 public key, Base58).
     *
     * @return string Normalized address string (same as input after trim; Base58 is case-sensitive)
     *
     * @throws InvalidArgumentException
     */
    public function validateAndNormalize(string $raw): string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            throw new InvalidArgumentException(__('Solana address is required.'));
        }

        if (str_contains($trimmed, ' ') || str_contains($trimmed, "\n") || str_contains($trimmed, "\t")) {
            throw new InvalidArgumentException(__('Enter a single Solana address with no extra spaces or lines.'));
        }

        if (strlen($trimmed) < 32 || strlen($trimmed) > 50) {
            throw new InvalidArgumentException(__('This does not look like a valid Solana address.'));
        }

        try {
            $decoded = Base58::decode($trimmed);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(__('This does not look like a valid Solana address.'), 0, $e);
        }

        if (strlen($decoded) !== 32) {
            throw new InvalidArgumentException(__('This does not look like a valid Solana address.'));
        }

        return $trimmed;
    }

    public function isValid(string $raw): bool
    {
        try {
            $this->validateAndNormalize($raw);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}

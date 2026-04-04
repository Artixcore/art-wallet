<?php

namespace App\Domain\Agents\Services;

use App\Models\AgentApiCredential;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

final class AgentCredentialVault
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function encryptPayload(array $payload): string
    {
        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    public function decryptPayload(AgentApiCredential $credential): array
    {
        try {
            $json = Crypt::decryptString($credential->encrypted_payload);

            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $e) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fingerprint(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function lastFourFromSecret(string $secret): string
    {
        $t = trim($secret);

        return strlen($t) <= 4 ? $t : substr($t, -4);
    }

    public function hashForAudit(string $secret): string
    {
        return Hash::make($secret);
    }
}

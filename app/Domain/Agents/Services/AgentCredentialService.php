<?php

namespace App\Domain\Agents\Services;

use App\Models\AgentApiCredential;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AgentCredentialService
{
    public function __construct(
        private readonly AgentCredentialVault $vault,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  e.g. ['api_key' => '...']
     */
    public function store(User $user, string $provider, array $payload, ?string $label = null): AgentApiCredential
    {
        $apiKey = (string) ($payload['api_key'] ?? '');
        $encrypted = $this->vault->encryptPayload($payload);

        return AgentApiCredential::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'label' => $label,
            'encrypted_payload' => $encrypted,
            'key_fingerprint' => $this->vault->fingerprint($payload),
            'last4' => $apiKey !== '' ? $this->vault->lastFourFromSecret($apiKey) : null,
            'metadata_json' => ['stored_at' => now()->toIso8601String()],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function decrypt(AgentApiCredential $credential): array
    {
        return $this->vault->decryptPayload($credential);
    }

    /**
     * @return array<string, mixed>
     */
    public function toMaskedArray(AgentApiCredential $credential): array
    {
        return [
            'id' => $credential->id,
            'provider' => $credential->provider,
            'label' => $credential->label,
            'last4' => $credential->last4,
            'created_at' => $credential->created_at?->toIso8601String(),
        ];
    }

    public function delete(User $user, AgentApiCredential $credential): void
    {
        if ((int) $credential->user_id !== (int) $user->id) {
            abort(403);
        }
        DB::transaction(function () use ($credential): void {
            $credential->providerBindings()->delete();
            $credential->delete();
        });
    }
}

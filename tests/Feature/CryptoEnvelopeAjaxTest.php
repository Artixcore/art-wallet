<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CryptoEnvelopeAjaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalVaultEnvelope(): array
    {
        return [
            'format' => 'artwallet-vault-v1',
            'alg' => 'AES-256-GCM',
            'kdf' => 'argon2id',
            'kdf_params' => [
                'salt' => base64_encode(random_bytes(16)),
                'iterations' => 3,
                'memoryKiB' => 32768,
                'parallelism' => 1,
                'hashLength' => 32,
            ],
            'nonce' => base64_encode(random_bytes(12)),
            'ciphertext' => base64_encode(random_bytes(32)),
            'aad_hint' => 'vault-v1',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalWrapEnvelope(string $publicId, int $recipientId): array
    {
        return [
            'format' => 'artwallet-wrap-v1',
            'alg' => 'AES-256-GCM',
            'ephemeral_pub' => base64_encode(random_bytes(32)),
            'nonce' => base64_encode(random_bytes(12)),
            'ciphertext' => base64_encode(random_bytes(32)),
            'info' => 'wrap-v1|'.$publicId.'|'.$recipientId,
        ];
    }

    public function test_store_wallet_requires_valid_vault_nonce_length(): void
    {
        $user = User::factory()->create();
        $vault = $this->minimalVaultEnvelope();
        $vault['nonce'] = base64_encode(random_bytes(11));

        $this->actingAs($user)->postJson('/ajax/wallets', [
            'public_wallet_id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'vault_version' => '1',
            'wallet_vault' => $vault,
        ])->assertStatus(422);
    }

    public function test_store_wallet_persists_ciphertext(): void
    {
        $user = User::factory()->create();
        $vault = $this->minimalVaultEnvelope();

        $this->actingAs($user)->postJson('/ajax/wallets', [
            'public_wallet_id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'vault_version' => '1',
            'label' => 't',
            'wallet_vault' => $vault,
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'vault_version' => '1',
            'public_wallet_id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        ]);
    }

    public function test_messaging_identity_rejects_short_public_key(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/ajax/messaging/identity', [
            'messaging_x25519_public_key' => base64_encode(random_bytes(31)),
        ])->assertStatus(422);
    }

    public function test_conversation_requires_matching_wrap_info(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $a->forceFill(['messaging_x25519_public_key' => base64_encode(random_bytes(32))])->save();
        $b->forceFill(['messaging_x25519_public_key' => base64_encode(random_bytes(32))])->save();

        $publicId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22';
        $badWrap = $this->minimalWrapEnvelope('wrong-uuid', (int) $a->id);

        $this->actingAs($a)->postJson('/ajax/conversations', [
            'public_id' => $publicId,
            'member_wraps' => [
                ['user_id' => $a->id, 'wrapped_conv_key' => $badWrap],
                ['user_id' => $b->id, 'wrapped_conv_key' => $this->minimalWrapEnvelope($publicId, (int) $b->id)],
            ],
        ])->assertStatus(422);
    }

    public function test_conversation_create_and_message_round_trip_indexes(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $a->forceFill(['messaging_x25519_public_key' => base64_encode(random_bytes(32))])->save();
        $b->forceFill(['messaging_x25519_public_key' => base64_encode(random_bytes(32))])->save();

        $publicId = 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380a33';
        $wrapA = $this->minimalWrapEnvelope($publicId, (int) $a->id);
        $wrapB = $this->minimalWrapEnvelope($publicId, (int) $b->id);

        $res = $this->actingAs($a)->postJson('/ajax/conversations', [
            'public_id' => $publicId,
            'member_wraps' => [
                ['user_id' => $a->id, 'wrapped_conv_key' => $wrapA],
                ['user_id' => $b->id, 'wrapped_conv_key' => $wrapB],
            ],
        ])->assertOk();

        $conversationId = (int) $res->json('conversation_id');

        $this->actingAs($a)->postJson("/ajax/conversations/{$conversationId}/messages", [
            'ciphertext' => base64_encode(random_bytes(32)),
            'nonce' => base64_encode(random_bytes(12)),
            'alg' => 'AES-256-GCM',
            'version' => '1',
        ])->assertOk()->assertJson(['message_index' => 0]);

        $this->actingAs($b)->postJson("/ajax/conversations/{$conversationId}/messages", [
            'ciphertext' => base64_encode(random_bytes(32)),
            'nonce' => base64_encode(random_bytes(12)),
            'alg' => 'AES-256-GCM',
            'version' => '1',
        ])->assertOk()->assertJson(['message_index' => 1]);
    }
}

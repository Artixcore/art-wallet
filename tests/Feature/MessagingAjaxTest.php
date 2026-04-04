<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MessagingAjaxTest extends TestCase
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

    public function test_conversation_list_returns_envelope(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/ajax/conversations')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 'OK');
    }

    public function test_message_idempotency_returns_replay_code(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $a->forceFill(['messaging_x25519_public_key' => base64_encode(random_bytes(32))])->save();
        $b->forceFill(['messaging_x25519_public_key' => base64_encode(random_bytes(32))])->save();

        $publicId = 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380a44';
        $wrapA = $this->minimalWrapEnvelope($publicId, (int) $a->id);
        $wrapB = $this->minimalWrapEnvelope($publicId, (int) $b->id);

        $res = $this->actingAs($a)->postJson('/ajax/conversations', [
            'public_id' => $publicId,
            'member_wraps' => [
                ['user_id' => $a->id, 'wrapped_conv_key' => $wrapA],
                ['user_id' => $b->id, 'wrapped_conv_key' => $wrapB],
            ],
        ])->assertOk();

        $conversationId = (int) $res->json('data.conversation_id');
        $idem = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        $payload = [
            'ciphertext' => base64_encode(random_bytes(32)),
            'nonce' => base64_encode(random_bytes(12)),
            'alg' => 'AES-256-GCM',
            'version' => '1',
            'idempotency_key' => $idem,
        ];

        $this->actingAs($a)->postJson("/ajax/conversations/{$conversationId}/messages", $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message_index', 0);

        $this->actingAs($a)->postJson("/ajax/conversations/{$conversationId}/messages", $payload)
            ->assertOk()
            ->assertJsonPath('code', 'MESSAGING_IDEMPOTENCY_REPLAY')
            ->assertJsonPath('data.message_index', 0);
    }

    public function test_non_member_cannot_post_message(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        $a->forceFill(['messaging_x25519_public_key' => base64_encode(random_bytes(32))])->save();
        $b->forceFill(['messaging_x25519_public_key' => base64_encode(random_bytes(32))])->save();

        $publicId = 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380a55';
        $wrapA = $this->minimalWrapEnvelope($publicId, (int) $a->id);
        $wrapB = $this->minimalWrapEnvelope($publicId, (int) $b->id);

        $res = $this->actingAs($a)->postJson('/ajax/conversations', [
            'public_id' => $publicId,
            'member_wraps' => [
                ['user_id' => $a->id, 'wrapped_conv_key' => $wrapA],
                ['user_id' => $b->id, 'wrapped_conv_key' => $wrapB],
            ],
        ])->assertOk();

        $conversationId = (int) $res->json('data.conversation_id');

        $this->actingAs($c)->postJson("/ajax/conversations/{$conversationId}/messages", [
            'ciphertext' => base64_encode(random_bytes(32)),
            'nonce' => base64_encode(random_bytes(12)),
            'alg' => 'AES-256-GCM',
            'version' => '1',
        ])->assertStatus(403)->assertJsonPath('success', false);
    }

    public function test_messaging_identity_returns_envelope(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/ajax/messaging/identity', [
            'messaging_x25519_public_key' => base64_encode(random_bytes(32)),
        ])->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.registered', true);
    }
}

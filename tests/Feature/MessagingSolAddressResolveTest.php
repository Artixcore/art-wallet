<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MessagingPrivacySetting;
use App\Models\User;
use App\Models\VerifiedWalletAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingSolAddressResolveTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_sol_address_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/ajax/messaging/resolve-sol-address', [
            'sol_address' => 'not-a-solana-address!!!',
        ])->assertStatus(422)->assertJsonPath('code', 'SOL_ADDRESS_INVALID');
    }

    public function test_not_found_returns_blind_success_envelope(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/ajax/messaging/resolve-sol-address', [
            'sol_address' => 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.contact_resolution_status', 'not_found');
    }

    public function test_resolves_verified_user_when_discoverable(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $b->forceFill([
            'messaging_x25519_public_key' => base64_encode(random_bytes(32)),
        ])->save();
        MessagingPrivacySetting::query()->create([
            'user_id' => $b->id,
            'read_receipts_enabled' => true,
            'typing_indicators_enabled' => true,
            'max_attachment_mb' => 10,
            'safety_warnings_enabled' => true,
            'discoverable_by_sol_address' => 'all_verified_users',
            'require_dm_approval' => false,
            'hide_profile_until_dm_accepted' => false,
            'settings_version' => 1,
        ]);
        VerifiedWalletAddress::query()->create([
            'user_id' => $b->id,
            'chain' => 'SOL',
            'address' => '11111111111111111111111111111112',
            'verified_at' => now(),
            'verification_source' => 'wallet_sync',
        ]);

        $this->actingAs($a)->postJson('/ajax/messaging/resolve-sol-address', [
            'sol_address' => '11111111111111111111111111111112',
        ])->assertOk()
            ->assertJsonPath('data.contact_resolution_status', 'resolved_artwallet_user')
            ->assertJsonPath('data.recipient.user_id', $b->id);
    }

    public function test_self_address_returns_422(): void
    {
        $a = User::factory()->create();
        VerifiedWalletAddress::query()->create([
            'user_id' => $a->id,
            'chain' => 'SOL',
            'address' => '11111111111111111111111111111112',
            'verified_at' => now(),
            'verification_source' => 'wallet_sync',
        ]);

        $this->actingAs($a)->postJson('/ajax/messaging/resolve-sol-address', [
            'sol_address' => '11111111111111111111111111111112',
        ])->assertStatus(422)->assertJsonPath('code', 'MESSAGING_SELF_CONVERSATION');
    }

    public function test_direct_conversation_conflict_returns_409(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        foreach ([$a, $b] as $u) {
            $u->forceFill([
                'messaging_x25519_public_key' => base64_encode(random_bytes(32)),
            ])->save();
        }

        $publicId = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $wrap = static fn (int $uid) => [
            'format' => 'artwallet-wrap-v1',
            'alg' => 'AES-256-GCM',
            'ephemeral_pub' => base64_encode(random_bytes(32)),
            'nonce' => base64_encode(random_bytes(12)),
            'ciphertext' => base64_encode(random_bytes(32)),
            'info' => 'wrap-v1|'.$publicId.'|'.$uid,
        ];

        $payload = [
            'type' => 'direct',
            'public_id' => $publicId,
            'member_wraps' => [
                ['user_id' => $a->id, 'wrapped_conv_key' => $wrap($a->id)],
                ['user_id' => $b->id, 'wrapped_conv_key' => $wrap($b->id)],
            ],
        ];

        $this->actingAs($a)->postJson('/ajax/conversations', $payload)->assertOk();
        $this->actingAs($a)->postJson('/ajax/conversations', $payload)->assertStatus(409)->assertJsonPath('code', 'CONFLICT');
    }
}

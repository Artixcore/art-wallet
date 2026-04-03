<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DeviceChallenge;
use App\Models\LoginTrustedDevice;
use App\Models\User;
use App\Services\DeviceTrustService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SecurityCenterAjaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_register_trusted_device_and_complete_challenge_with_sodium_signature(): void
    {
        if (! function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('libsodium not available.');
        }

        $user = User::factory()->create();
        $kp = sodium_crypto_sign_keypair();
        $pk = sodium_crypto_sign_publickey($kp);
        $sk = sodium_crypto_sign_secretkey($kp);
        $pkB64 = base64_encode($pk);

        $this->actingAs($user)->postJson('/ajax/security/trusted-devices', [
            'public_key' => $pkB64,
            'fingerprint_signals_json' => ['test' => true],
        ])->assertOk()->assertJson(['ok' => true]);

        $device = LoginTrustedDevice::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->postJson('/ajax/security/challenges', [
            'purpose' => DeviceTrustService::PURPOSE_NEW_DEVICE,
        ])->assertOk()->assertJsonPath('ok', true);

        $challenge = DeviceChallenge::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        $trust = app(DeviceTrustService::class);
        $msg = $trust->signingMessage($challenge, (int) $user->id, (int) $device->trust_version);
        $sig = sodium_crypto_sign_detached($msg, $sk);

        $this->actingAs($user)->postJson('/ajax/security/challenges/approve', [
            'challenge_public_uuid' => $challenge->public_uuid,
            'login_trusted_device_id' => $device->id,
            'signature' => base64_encode($sig),
        ])->assertOk()->assertJson(['ok' => true]);
    }

    public function test_backup_state_mnemonic_verified_persists(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/ajax/security/backup-state', [
            'mnemonic_verified' => true,
        ])->assertOk()->assertJson(['ok' => true]);

        $res = $this->actingAs($user)->getJson('/ajax/security/backup-state');
        $res->assertOk();
        $this->assertNotNull($res->json('backup_state.mnemonic_verified_at'));
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalRecoveryKitEnvelope(int $userId): array
    {
        return [
            'format' => 'artwallet-recovery-kit-v1',
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
            'aad_hint' => 'artwallet-recovery-kit-v1|'.$userId.'|1',
            'kit_version' => 1,
        ];
    }

    public function test_recovery_kit_store_requires_aad_user_match(): void
    {
        $user = User::factory()->create();
        $kit = $this->minimalRecoveryKitEnvelope((int) $user->id + 99);

        $this->actingAs($user)->postJson('/ajax/security/recovery-kit', [
            'recovery_kit' => $kit,
        ])->assertStatus(422);
    }

    public function test_security_center_page_renders_for_verified_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/security')
            ->assertOk()
            ->assertSee('Security center', false);
    }

    public function test_recovery_kit_store_persists(): void
    {
        $user = User::factory()->create();
        $kit = $this->minimalRecoveryKitEnvelope((int) $user->id);

        $this->actingAs($user)->postJson('/ajax/security/recovery-kit', [
            'recovery_kit' => $kit,
        ])->assertOk()->assertJson(['ok' => true]);

        $this->actingAs($user)->getJson('/ajax/security/recovery-kit')
            ->assertOk()
            ->assertJsonPath('recovery_kit.version', '1');
    }
}

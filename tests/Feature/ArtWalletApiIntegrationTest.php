<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Webhooks\Services\OutboundWebhookSigner;
use App\Events\Realtime\UserDomainEvent;
use App\Models\IntegrationEndpoint;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArtWalletApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_returns_envelope_with_tokens(): void
    {
        $user = User::factory()->create(['email' => 'api-user@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_id' => 'device-test-1',
            'platform' => 'ios',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 'OK')
            ->assertJsonStructure([
                'data' => ['access_token', 'refresh_token', 'expires_in', 'device_id'],
            ]);
    }

    public function test_api_login_invalid_credentials_returns_401_envelope(): void
    {
        $user = User::factory()->create(['email' => 'api-invalid@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_id' => 'device-test-1',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNAUTHORIZED');
    }

    public function test_api_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_api_me_returns_user_with_bearer_and_device_header(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['api:v1']);
        $device = $user->apiDevices()->create([
            'device_id' => 'dev-1',
            'name' => 't',
            'platform' => 'ios',
        ]);
        $token->accessToken->forceFill(['api_device_id' => $device->id])->save();

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->withHeader('X-ArtWallet-Device-Id', 'dev-1')
            ->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_refresh_token_reuse_returns_token_reuse_code(): void
    {
        $user = User::factory()->create(['email' => 'api-reuse@example.com']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_id' => 'device-reuse',
        ]);

        $login->assertOk();
        $refresh1 = $login->json('data.refresh_token');

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refresh1,
            'device_id' => 'device-reuse',
        ])->assertOk();

        $reuse = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refresh1,
            'device_id' => 'device-reuse',
        ]);

        $reuse->assertStatus(401)
            ->assertJsonPath('code', 'TOKEN_REUSE_DETECTED');
    }

    public function test_validation_error_returns_422_envelope(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'x',
            'device_id' => '!!!',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_inbound_webhook_signature_verification(): void
    {
        $user = User::factory()->create();
        $endpoint = IntegrationEndpoint::query()->create([
            'user_id' => $user->id,
            'name' => 'Test',
            'url' => 'https://example.com/hook',
            'secret_hash' => hash('sha256', 'ignored'),
            'secret_cipher' => 'test-secret-plain',
            'scopes_json' => ['portfolio.read'],
            'enabled' => true,
        ]);

        $signer = app(OutboundWebhookSigner::class);
        $body = [
            'event_type' => 'ping',
            'event_id' => '00000000-0000-0000-0000-000000000001',
            'occurred_at' => now()->toIso8601String(),
            'data' => [],
        ];
        $raw = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $signed = $signer->sign('test-secret-plain', $body);

        $this->call('POST', '/api/webhooks/inbound/'.$endpoint->id, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ARTWALLET_SIGNATURE' => $signed['signature'],
            'HTTP_X_ARTWALLET_TIMESTAMP' => $signed['timestamp'],
        ], $raw)->assertOk()
            ->assertJsonPath('success', true);

        $this->call('POST', '/api/webhooks/inbound/'.$endpoint->id, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ARTWALLET_SIGNATURE' => 'bad',
            'HTTP_X_ARTWALLET_TIMESTAMP' => $signed['timestamp'],
        ], $raw)->assertStatus(401);
    }

    public function test_user_domain_event_is_broadcastable(): void
    {
        $event = new UserDomainEvent(1, 'uuid', 'test.event', ['foo' => 'bar']);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }
}

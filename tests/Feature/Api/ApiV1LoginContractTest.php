<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contract tests: stable JSON shape for mobile/API clients (AjaxEnvelope on /api/v1).
 */
final class ApiV1LoginContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_response_has_required_envelope_fields(): void
    {
        $user = User::factory()->create(['email' => 'contract-login@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_id' => 'contract-device-1',
            'platform' => 'ios',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'message',
                'severity',
                'data' => [
                    'access_token',
                    'refresh_token',
                    'expires_in',
                    'device_id',
                ],
                'errors',
                'meta',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 'OK');
    }

    public function test_login_validation_error_has_envelope_shape(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'not-email',
            'password' => 'x',
            'device_id' => 'bad!!!',
        ])
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'code',
                'message',
                'severity',
                'errors',
                'meta',
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }
}

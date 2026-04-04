<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\ApiDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies device-bound Sanctum tokens require X-ArtWallet-Device-Id (fail-closed).
 */
final class ApiDeviceBindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_device_header_returns_401_envelope(): void
    {
        $user = User::factory()->create();
        $device = ApiDevice::query()->create([
            'user_id' => $user->id,
            'device_id' => 'bound-device-1',
            'name' => 'Test',
            'platform' => 'ios',
        ]);

        $token = $user->createToken('api-test', ['api:v1']);
        $token->accessToken->forceFill(['api_device_id' => $device->id])->save();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }

    public function test_wrong_device_header_returns_403(): void
    {
        $user = User::factory()->create();
        $device = ApiDevice::query()->create([
            'user_id' => $user->id,
            'device_id' => 'correct-id',
            'name' => 'Test',
            'platform' => 'ios',
        ]);

        $token = $user->createToken('api-test', ['api:v1']);
        $token->accessToken->forceFill(['api_device_id' => $device->id])->save();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->withHeader('X-ArtWallet-Device-Id', 'wrong-id')
            ->getJson('/api/v1/me')
            ->assertForbidden();
    }
}

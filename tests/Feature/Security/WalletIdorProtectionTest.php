<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifies users cannot read another user's wallet resources (IDOR / authorization).
 */
final class WalletIdorProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_user_cannot_view_another_users_wallet_vault(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $bobWallet = Wallet::query()->create([
            'user_id' => $bob->id,
            'label' => 'Bob wallet',
            'public_wallet_id' => (string) Str::uuid(),
            'vault_version' => '1',
            'kdf_params' => [],
            'wallet_vault_ciphertext' => json_encode(['format' => 'test'], JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($alice)
            ->getJson("/ajax/wallets/{$bobWallet->id}/vault")
            ->assertForbidden();
    }
}

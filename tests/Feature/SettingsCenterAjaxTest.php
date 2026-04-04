<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSecurityPolicy;
use App\Models\UserSetting;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SettingsCenterAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_snapshot_requires_auth(): void
    {
        $this->getJson('/ajax/settings')->assertUnauthorized();
    }

    public function test_settings_snapshot_returns_envelope(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/ajax/settings')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.theme', 'system')
            ->assertJsonPath('data.security_policy.idle_timeout_minutes', 60);
    }

    public function test_update_user_settings_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/ajax/settings/user', [
            'theme' => 'invalid',
            'settings_version' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_update_user_settings_persists_and_returns_success(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/ajax/settings');
        $row = UserSetting::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($row);

        $this->actingAs($user)->putJson('/ajax/settings/user', [
            'theme' => 'dark',
            'locale' => 'en',
            'timezone' => 'UTC',
            'settings_version' => $row->settings_version,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.theme', 'dark');

        $row->refresh();
        $this->assertSame('dark', $row->theme);
    }

    public function test_security_policy_conflict_returns_409(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/ajax/settings');
        $sec = UserSecurityPolicy::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($sec);

        UserSecurityPolicy::query()->where('id', $sec->id)->update([
            'settings_version' => $sec->settings_version + 1,
        ]);

        $this->actingAs($user)->putJson('/ajax/settings/security-policy', [
            'idle_timeout_minutes' => 30,
            'notify_new_device_login' => true,
            'settings_version' => $sec->settings_version,
        ])->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CONFLICT');
    }

    public function test_security_policy_relaxed_requires_step_up(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/ajax/settings');
        $sec = UserSecurityPolicy::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($sec);

        $this->actingAs($user)->putJson('/ajax/settings/security-policy', [
            'idle_timeout_minutes' => 120,
            'notify_new_device_login' => true,
            'settings_version' => $sec->settings_version,
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_step_up_then_security_policy_relaxed_succeeds(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/ajax/settings');
        $sec = UserSecurityPolicy::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($sec);

        $step = $this->actingAs($user)->postJson('/ajax/settings/step-up', [
            'password' => 'password',
        ]);
        $step->assertOk()->assertJsonPath('success', true);
        $token = $step->json('data.step_up_token');
        $this->assertIsString($token);
        $this->assertSame(48, strlen($token));

        $this->actingAs($user)->putJson('/ajax/settings/security-policy', [
            'idle_timeout_minutes' => 120,
            'notify_new_device_login' => true,
            'settings_version' => $sec->settings_version,
            'step_up_token' => $token,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.security_policy.idle_timeout_minutes', 120);
    }

    public function test_audit_log_lists_entries_after_change(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/ajax/settings');
        $row = UserSetting::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($row);

        $this->actingAs($user)->putJson('/ajax/settings/user', [
            'theme' => 'light',
            'settings_version' => $row->settings_version,
        ])->assertOk();

        $this->actingAs($user)->getJson('/ajax/settings/audit')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['logs']]);
    }

    public function test_wallet_settings_requires_owner(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $u1->id,
            'label' => 't',
            'public_wallet_id' => (string) Str::uuid(),
            'vault_version' => '1',
            'kdf_params' => [],
            'wallet_vault_ciphertext' => 'x',
        ]);

        $this->actingAs($u2)->getJson('/ajax/wallets/'.$wallet->id.'/settings-bundle')
            ->assertForbidden();
    }

    public function test_wallet_settings_bundle_ok_for_owner(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'label' => 't',
            'public_wallet_id' => (string) Str::uuid(),
            'vault_version' => '1',
            'kdf_params' => [],
            'wallet_vault_ciphertext' => 'x',
        ]);

        $this->actingAs($user)->getJson('/ajax/wallets/'.$wallet->id.'/settings-bundle')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.wallet_settings.show_testnet_assets', false);
    }
}

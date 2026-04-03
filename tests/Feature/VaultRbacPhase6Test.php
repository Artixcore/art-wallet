<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Artixcore\ArtGate\Models\Permission;
use Artixcore\ArtGate\Models\Role;
use Artixcore\ArtGate\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VaultRbacPhase6Test extends TestCase
{
    use RefreshDatabase;

    public function test_registered_gate_delegates_to_vault_permissions(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'phase6-gate',
            'name' => 'Phase6 Gate',
            'status' => 'active',
        ]);

        $permission = Permission::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'phase6.gate.test',
            'is_wildcard_parent' => false,
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'phase6_gate_role',
            'activation_state' => 'active',
        ]);

        $role->permissions()->attach($permission->id, [
            'tenant_id' => $tenant->id,
            'granted_at' => now(),
            'source' => 'direct',
        ]);

        $user = User::factory()->create();
        $user->assignRole('phase6_gate_role', $tenant->id);

        config(['artgate.default_tenant_id' => $tenant->id]);

        $ability = (string) config('artgate.gate.ability');

        $this->assertTrue(Gate::forUser($user)->allows($ability, ['phase6.gate.test']));
        $this->assertFalse(Gate::forUser($user)->allows($ability, ['phase6.gate.denied']));
    }

    public function test_vault_role_middleware_allows_assigned_user(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'phase6-mw',
            'name' => 'Phase6 MW',
            'status' => 'active',
        ]);

        Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'phase6_mw_role',
            'activation_state' => 'active',
        ]);

        $user = User::factory()->create();
        $user->assignRole('phase6_mw_role', $tenant->id);

        Route::middleware(['web', 'artgate.ensure-role:phase6_mw_role'])->get('/_vaultrbac_phase6_role', fn () => 'ok');

        config(['artgate.default_tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/_vaultrbac_phase6_role')->assertOk()->assertSee('ok');
    }

    public function test_vault_any_role_middleware_accepts_alternate_role(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'phase6-any',
            'name' => 'Phase6 Any',
            'status' => 'active',
        ]);

        foreach (['phase6_alt_a', 'phase6_alt_b'] as $name) {
            Role::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'activation_state' => 'active',
            ]);
        }

        $user = User::factory()->create();
        $user->assignRole('phase6_alt_b', $tenant->id);

        Route::middleware(['web', 'artgate.ensure-any-role:phase6_alt_a|phase6_alt_b'])->get('/_vaultrbac_phase6_any', fn () => 'yes');

        config(['artgate.default_tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/_vaultrbac_phase6_any')->assertOk()->assertSee('yes');
    }

    public function test_blade_vaultcan_and_vaultrole(): void
    {
        config(['artgate.blade.enabled' => true]);

        $tenant = Tenant::query()->create([
            'slug' => 'phase6-blade',
            'name' => 'Phase6 Blade',
            'status' => 'active',
        ]);

        $permission = Permission::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'phase6.blade.perm',
            'is_wildcard_parent' => false,
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'phase6_blade_role',
            'activation_state' => 'active',
        ]);

        $role->permissions()->attach($permission->id, [
            'tenant_id' => $tenant->id,
            'granted_at' => now(),
            'source' => 'direct',
        ]);

        $user = User::factory()->create();
        $user->assignRole('phase6_blade_role', $tenant->id);

        $this->actingAs($user);
        config(['artgate.default_tenant_id' => $tenant->id]);

        $can = Blade::render("@artgatecan('phase6.blade.perm') YES @else NO @endartgatecan");
        $this->assertStringContainsString('YES', $can);
        $this->assertStringNotContainsString('NO', $can);

        $roleBlade = Blade::render("@artgaterole('phase6_blade_role') IN @else OUT @endartgaterole");
        $this->assertStringContainsString('IN', $roleBlade);
    }

    public function test_artisan_artgate_doctor_succeeds_after_migrate(): void
    {
        $this->artisan('artgate:doctor')->assertExitCode(0);
    }

    public function test_artisan_sync_permissions_inserts_configured_rows(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'phase6-sync',
            'name' => 'Phase6 Sync',
            'status' => 'active',
        ]);

        config([
            'artgate.sync.permissions' => [
                'phase6_sync_plain',
                ['name' => 'phase6_sync_rich', 'permission_group' => 'g'],
            ],
        ]);

        $this->artisan('artgate:sync-permissions', ['--tenant' => (string) $tenant->id])->assertExitCode(0);

        $this->assertDatabaseHas((string) config('artgate.tables.permissions'), [
            'tenant_id' => $tenant->id,
            'name' => 'phase6_sync_plain',
        ]);
        $this->assertDatabaseHas((string) config('artgate.tables.permissions'), [
            'tenant_id' => $tenant->id,
            'name' => 'phase6_sync_rich',
            'permission_group' => 'g',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\Team;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VaultRbacTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'vault.permission:phase4.view'])
            ->get('/__vault_phase4_perm', fn () => response('ok', 200));

        Route::middleware(['web', 'auth', 'vault.tenant', 'vault.tenant.member'])
            ->get('/__vault_phase4_member', fn () => response('member', 200));
    }

    public function test_request_tenant_header_is_used_for_authorization(): void
    {
        config([
            'vaultrbac.tenant.sources' => [
                ['driver' => 'header', 'name' => 'X-Tenant-Id', 'cast' => 'int'],
            ],
            'vaultrbac.default_tenant_id' => null,
        ]);

        $tenantA = Tenant::query()->create([
            'slug' => 'a',
            'name' => 'A',
            'status' => 'active',
        ]);

        $tenantB = Tenant::query()->create([
            'slug' => 'b',
            'name' => 'B',
            'status' => 'active',
        ]);

        $permission = Permission::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'phase4.view',
            'is_wildcard_parent' => false,
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'viewer',
            'activation_state' => 'active',
        ]);

        $role->permissions()->attach($permission->id, [
            'tenant_id' => $tenantA->id,
            'granted_at' => now(),
            'source' => 'direct',
        ]);

        $user = User::factory()->create();
        $user->assignRole('viewer', $tenantA->id);

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenantB->id)
            ->get('/__vault_phase4_perm')
            ->assertForbidden();

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenantA->id)
            ->get('/__vault_phase4_perm')
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_membership_middleware_rejects_unknown_tenant(): void
    {
        config([
            'vaultrbac.tenant.sources' => [
                ['driver' => 'header', 'name' => 'X-Tenant-Id', 'cast' => 'int'],
            ],
        ]);

        $tenantA = Tenant::query()->create([
            'slug' => 'a',
            'name' => 'A',
            'status' => 'active',
        ]);

        $tenantB = Tenant::query()->create([
            'slug' => 'b',
            'name' => 'B',
            'status' => 'active',
        ]);

        Role::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'viewer',
            'activation_state' => 'active',
        ]);

        $user = User::factory()->create();
        $user->assignRole('viewer', $tenantA->id);

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenantB->id)
            ->get('/__vault_phase4_member')
            ->assertForbidden();

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenantA->id)
            ->get('/__vault_phase4_member')
            ->assertOk()
            ->assertSee('member');
    }

    public function test_team_header_scopes_role_assignments(): void
    {
        config([
            'vaultrbac.tenant.sources' => [
                ['driver' => 'header', 'name' => 'X-Tenant-Id', 'cast' => 'int'],
            ],
            'vaultrbac.team.sources' => [
                ['driver' => 'header', 'name' => 'X-Team-Id', 'cast' => 'int'],
            ],
            'vaultrbac.default_tenant_id' => null,
        ]);

        $tenant = Tenant::query()->create([
            'slug' => 'org',
            'name' => 'Org',
            'status' => 'active',
        ]);

        $teamAlpha = Team::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Alpha',
            'slug' => 'alpha',
        ]);

        $teamBeta = Team::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Beta',
            'slug' => 'beta',
        ]);

        $permission = Permission::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'phase4.view',
            'is_wildcard_parent' => false,
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'team-viewer',
            'activation_state' => 'active',
        ]);

        $role->permissions()->attach($permission->id, [
            'tenant_id' => $tenant->id,
            'granted_at' => now(),
            'source' => 'direct',
        ]);

        $user = User::factory()->create();
        $user->assignRole('team-viewer', $tenant->id, $teamAlpha->id);

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->withHeader('X-Team-Id', (string) $teamBeta->id)
            ->get('/__vault_phase4_perm')
            ->assertForbidden();

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->withHeader('X-Team-Id', (string) $teamAlpha->id)
            ->get('/__vault_phase4_perm')
            ->assertOk();
    }
}

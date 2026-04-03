<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Artwallet\VaultRbac\Facades\VaultRbac;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\RoleHierarchy;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VaultRbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_grants_exact_permission_when_default_tenant_is_set(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'acme',
            'name' => 'Acme',
            'status' => 'active',
        ]);

        $permission = Permission::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'posts.edit',
            'is_wildcard_parent' => false,
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'editor',
            'activation_state' => 'active',
        ]);

        $role->permissions()->attach($permission->id, [
            'tenant_id' => $tenant->id,
            'granted_at' => now(),
            'source' => 'direct',
        ]);

        $user = User::factory()->create();
        $user->assignRole('editor', $tenant->id);

        $this->actingAs($user);
        config(['vaultrbac.default_tenant_id' => $tenant->id]);

        $this->assertTrue(VaultRbac::check('posts.edit'));
        $this->assertFalse(VaultRbac::check('posts.delete'));
    }

    public function test_direct_deny_overrides_role_allow(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'acme',
            'name' => 'Acme',
            'status' => 'active',
        ]);

        $permission = Permission::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'vault.access',
            'is_wildcard_parent' => false,
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'member',
            'activation_state' => 'active',
        ]);

        $role->permissions()->attach($permission->id, [
            'tenant_id' => $tenant->id,
            'granted_at' => now(),
            'source' => 'direct',
        ]);

        $user = User::factory()->create();
        $user->assignRole('member', $tenant->id);
        $user->givePermissionTo('vault.access', $tenant->id, null, 'deny');

        $this->actingAs($user);
        config(['vaultrbac.default_tenant_id' => $tenant->id]);

        $this->assertFalse(VaultRbac::check('vault.access'));
    }

    public function test_hierarchy_inherits_parent_role_permissions(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'acme',
            'name' => 'Acme',
            'status' => 'active',
        ]);

        $permission = Permission::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'reports.view',
            'is_wildcard_parent' => false,
        ]);

        $parent = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'viewer',
            'activation_state' => 'active',
        ]);

        $child = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'viewer_plus',
            'activation_state' => 'active',
        ]);

        $parent->permissions()->attach($permission->id, [
            'tenant_id' => $tenant->id,
            'granted_at' => now(),
            'source' => 'direct',
        ]);

        RoleHierarchy::query()->create([
            'tenant_id' => $tenant->id,
            'child_role_id' => $child->id,
            'parent_role_id' => $parent->id,
        ]);

        $user = User::factory()->create();
        $user->assignRole('viewer_plus', $tenant->id);

        $this->actingAs($user);
        config(['vaultrbac.default_tenant_id' => $tenant->id]);

        $this->assertTrue(VaultRbac::check('reports.view'));
    }
}

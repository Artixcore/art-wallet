<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Concerns;

use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\Tenant;
use Artwallet\VaultRbac\Tests\Fixtures\User;
use Artwallet\VaultRbac\VaultRbac;
use Illuminate\Support\Facades\DB;

trait InteractsWithVaultRbac
{
    protected function createTenant(array $attributes = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant-'.uniqid(),
        ], $attributes));
    }

    protected function createUser(array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'Tester',
            'email' => 'user-'.uniqid('', true).'@example.test',
            'password' => 'secret',
        ], $attributes));
    }

    protected function createRoleForTenant(Tenant $tenant, string $name = 'member'): Role
    {
        return Role::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => $name,
            'activation_state' => 'active',
        ]);
    }

    protected function createPermissionForTenant(Tenant $tenant, string $name): Permission
    {
        return Permission::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => $name,
            'is_wildcard_parent' => false,
        ]);
    }

    protected function attachPermissionToRole(Role $role, Permission $permission, Tenant $tenant): void
    {
        $pivot = config('vaultrbac.tables.role_permission');
        DB::table($pivot)->insert([
            'role_id' => $role->getKey(),
            'permission_id' => $permission->getKey(),
            'tenant_id' => $tenant->getKey(),
            'granted_at' => now(),
            'expires_at' => null,
            'source' => 'direct',
            'condition_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function vault(): VaultRbac
    {
        return $this->app->make(VaultRbac::class);
    }

    /**
     * @param  array<string, mixed>  $extraConfig
     */
    protected function withDefaultTenant(Tenant $tenant, array $extraConfig = []): void
    {
        config(array_merge([
            'vaultrbac.default_tenant_id' => $tenant->getKey(),
        ], $extraConfig));
    }
}

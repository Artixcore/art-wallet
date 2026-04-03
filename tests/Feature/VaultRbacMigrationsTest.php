<?php

declare(strict_types=1);

namespace Tests\Feature;

use Artwallet\VaultRbac\Database\VaultrbacTables;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class VaultRbacMigrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_vault_rbac_tables_exist(): void
    {
        $keys = [
            'tenants',
            'teams',
            'permissions',
            'roles',
            'permission_conditions',
            'approval_requests',
            'role_permission',
            'model_roles',
            'model_permissions',
            'role_hierarchy',
            'permission_scopes',
            'permission_inheritance',
            'encrypted_payloads',
            'super_user_actions',
            'cache_versions',
            'audit_events',
        ];

        foreach ($keys as $key) {
            $this->assertTrue(
                Schema::hasTable(VaultrbacTables::name($key)),
                "Expected table [{$key}] to exist.",
            );
        }
    }
}

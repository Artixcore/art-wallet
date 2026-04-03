<?php

declare(strict_types=1);

namespace Tests\Feature;

use Artixcore\ArtGate\Database\ArtGateTables;
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
            'tenant_roles',
            'tenant_permissions',
            'temporary_permissions',
            'role_expirations',
            'permission_cache_versions',
            'encrypted_metadata',
            'super_user_actions',
            'cache_versions',
            'audit_logs',
        ];

        foreach ($keys as $key) {
            $this->assertTrue(
                Schema::hasTable(ArtGateTables::name($key)),
                "Expected table [{$key}] to exist.",
            );
        }
    }
}

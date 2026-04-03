<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Feature;

use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;

final class VaultRbacHelpersFeatureTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_vault_rbac_can_helper_matches_resolver(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'h');
        $perm = $this->createPermissionForTenant($tenant, 'helper.perm');
        $this->attachPermissionToRole($role, $perm, $tenant);
        $this->vault()->assignRole($user, $role, $tenant->getKey());

        $this->actingAs($user);

        self::assertTrue(vault_rbac_can('helper.perm'));
    }
}

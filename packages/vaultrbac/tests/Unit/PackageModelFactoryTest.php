<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Unit;

use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\Team;
use Artwallet\VaultRbac\Models\Tenant;
use Artwallet\VaultRbac\Tests\TestCase;

final class PackageModelFactoryTest extends TestCase
{
    public function test_core_models_can_be_created_via_factory(): void
    {
        $tenant = Tenant::factory()->create();
        $this->assertDatabaseHas($tenant->getTable(), ['id' => $tenant->getKey()]);

        $team = Team::factory()->for($tenant)->create();
        $this->assertSame($tenant->getKey(), $team->tenant_id);

        $role = Role::factory()->forTenant($tenant)->create();
        $this->assertSame($tenant->getKey(), $role->tenant_id);

        $permission = Permission::factory()->forTenant($tenant)->create();
        $this->assertSame($tenant->getKey(), $permission->tenant_id);
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Unit;

use Artwallet\VaultRbac\Hierarchy\EloquentRoleHierarchyProvider;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\RoleHierarchy;
use Artwallet\VaultRbac\Models\Tenant;
use Artwallet\VaultRbac\Tests\TestCase;

final class EloquentRoleHierarchyProviderTest extends TestCase
{
    public function test_cycle_does_not_cause_infinite_expansion(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'h-'.uniqid(),
            'name' => 'H',
            'status' => 'active',
        ]);

        $a = Role::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'a-'.uniqid(),
            'activation_state' => 'active',
        ]);
        $b = Role::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'b-'.uniqid(),
            'activation_state' => 'active',
        ]);

        RoleHierarchy::query()->create([
            'tenant_id' => $tenant->getKey(),
            'child_role_id' => $a->getKey(),
            'parent_role_id' => $b->getKey(),
        ]);
        RoleHierarchy::query()->create([
            'tenant_id' => $tenant->getKey(),
            'child_role_id' => $b->getKey(),
            'parent_role_id' => $a->getKey(),
        ]);

        $provider = new EloquentRoleHierarchyProvider;
        $ancestors = $provider->ancestors($a->getKey(), $tenant->getKey());

        self::assertContains($b->getKey(), $ancestors);
        self::assertLessThanOrEqual(256, count($ancestors));
    }

    public function test_max_expanded_nodes_caps_traversal(): void
    {
        config(['vaultrbac.hierarchy.max_expanded_nodes' => 4]);

        $tenant = Tenant::query()->create([
            'slug' => 'deep-'.uniqid(),
            'name' => 'D',
            'status' => 'active',
        ]);

        $roles = [];
        for ($i = 0; $i < 6; $i++) {
            $roles[] = Role::query()->create([
                'tenant_id' => $tenant->getKey(),
                'name' => 'r'.$i.'-'.uniqid(),
                'activation_state' => 'active',
            ]);
        }

        for ($i = 1; $i < 6; $i++) {
            RoleHierarchy::query()->create([
                'tenant_id' => $tenant->getKey(),
                'child_role_id' => $roles[$i - 1]->getKey(),
                'parent_role_id' => $roles[$i]->getKey(),
            ]);
        }

        $provider = new EloquentRoleHierarchyProvider;
        $ancestors = $provider->ancestors($roles[0]->getKey(), $tenant->getKey());

        self::assertLessThanOrEqual(4, count($ancestors));
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Integration;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Models\PermissionInheritance;
use Artwallet\VaultRbac\Resolvers\DatabasePermissionResolver;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;

/**
 * Documents current resolver behavior against catalog metadata not yet wired into resolution.
 *
 * @group integration
 */
final class CatalogBehaviorIntegrationTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_wildcard_parent_flag_does_not_grant_child_ability_by_prefix(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'r');
        $parent = $this->createPermissionForTenant($tenant, 'posts.*');
        $parent->forceFill(['is_wildcard_parent' => true])->save();

        $this->attachPermissionToRole($role, $parent, $tenant);
        $this->vault()->assignRole($user, $role, $tenant->getKey());

        $resolver = $this->app->make(DatabasePermissionResolver::class);
        $ctx = new AuthorizationContext($user, $tenant->getKey(), null, null, null, 'testing');

        self::assertFalse($resolver->authorize($ctx, 'posts.edit', null));
    }

    public function test_permission_inheritance_row_alone_does_not_grant_descendant_ability(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'r');
        $ancestor = $this->createPermissionForTenant($tenant, 'parent.perm');
        $descendant = $this->createPermissionForTenant($tenant, 'child.perm');

        PermissionInheritance::query()->create([
            'tenant_id' => $tenant->getKey(),
            'ancestor_permission_id' => $ancestor->getKey(),
            'descendant_permission_id' => $descendant->getKey(),
        ]);

        $this->attachPermissionToRole($role, $ancestor, $tenant);
        $this->vault()->assignRole($user, $role, $tenant->getKey());

        $resolver = $this->app->make(DatabasePermissionResolver::class);
        $ctx = new AuthorizationContext($user, $tenant->getKey(), null, null, null, 'testing');

        self::assertTrue($resolver->authorize($ctx, 'parent.perm', null));
        self::assertFalse($resolver->authorize($ctx, 'child.perm', null));
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Integration;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Enums\PermissionEffect;
use Artwallet\VaultRbac\Models\ModelPermission;
use Artwallet\VaultRbac\Models\ModelRole;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Resolvers\DatabasePermissionResolver;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;

/**
 * @group integration
 */
final class DatabasePermissionResolverIntegrationTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_role_grants_permission_for_ability(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'editor');
        $perm = $this->createPermissionForTenant($tenant, 'posts.edit');
        $this->attachPermissionToRole($role, $perm, $tenant);

        $this->vault()->assignRole($user, $role, $tenant->getKey());

        $resolver = $this->app->make(DatabasePermissionResolver::class);
        $ctx = new AuthorizationContext($user, $tenant->getKey(), null, null, null, 'testing');

        self::assertTrue($resolver->authorize($ctx, 'posts.edit', null));
    }

    public function test_direct_deny_effect_denies_even_when_role_would_allow(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'editor');
        $perm = $this->createPermissionForTenant($tenant, 'posts.edit');
        $this->attachPermissionToRole($role, $perm, $tenant);
        $this->vault()->assignRole($user, $role, $tenant->getKey());

        ModelPermission::query()->create([
            'tenant_id' => $tenant->getKey(),
            'team_id' => null,
            'team_key' => 0,
            'permission_id' => $perm->getKey(),
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->getKey(),
            'effect' => PermissionEffect::Deny,
            'assigned_at' => now(),
            'expires_at' => null,
            'suspended_at' => null,
        ]);

        $resolver = $this->app->make(DatabasePermissionResolver::class);
        $ctx = new AuthorizationContext($user, $tenant->getKey(), null, null, null, 'testing');

        self::assertFalse($resolver->authorize($ctx, 'posts.edit', null));
    }

    public function test_unknown_ability_denies_when_definition_required(): void
    {
        config(['vaultrbac.require_permission_definition' => true]);

        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'editor');
        $perm = $this->createPermissionForTenant($tenant, 'posts.edit');
        $this->attachPermissionToRole($role, $perm, $tenant);
        $this->vault()->assignRole($user, $role, $tenant->getKey());

        $resolver = $this->app->make(DatabasePermissionResolver::class);
        $ctx = new AuthorizationContext($user, $tenant->getKey(), null, null, null, 'testing');

        self::assertFalse($resolver->authorize($ctx, 'missing.ability', null));
    }

    public function test_role_from_other_tenant_is_ignored(): void
    {
        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $this->withDefaultTenant($tenantA);

        $user = $this->createUser();

        $roleB = Role::query()->create([
            'tenant_id' => $tenantB->getKey(),
            'name' => 'only-b',
            'activation_state' => 'active',
        ]);
        $permB = $this->createPermissionForTenant($tenantB, 'secret.act');
        $this->attachPermissionToRole($roleB, $permB, $tenantB);

        ModelRole::query()->create([
            'tenant_id' => $tenantA->getKey(),
            'team_id' => null,
            'team_key' => 0,
            'role_id' => $roleB->getKey(),
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->getKey(),
            'assigned_at' => now(),
        ]);

        $resolver = $this->app->make(DatabasePermissionResolver::class);
        $ctx = new AuthorizationContext($user, $tenantA->getKey(), null, null, null, 'testing');

        self::assertFalse($resolver->authorize($ctx, 'secret.act', null));
    }
}

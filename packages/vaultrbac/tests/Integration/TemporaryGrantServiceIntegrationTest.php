<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Integration;

use Artwallet\VaultRbac\Api\Dto\TemporaryGrantData;
use Artwallet\VaultRbac\Resolvers\DatabasePermissionResolver;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;
use Artwallet\VaultRbac\Context\AuthorizationContext;
use Carbon\CarbonImmutable;

/**
 * @group integration
 */
final class TemporaryGrantServiceIntegrationTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_expired_temporary_role_does_not_authorize(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'temp');
        $perm = $this->createPermissionForTenant($tenant, 'temp.act');
        $this->attachPermissionToRole($role, $perm, $tenant);

        $from = CarbonImmutable::parse('2026-01-01 00:00:00');
        $until = CarbonImmutable::parse('2026-01-02 00:00:00');
        $grant = new TemporaryGrantData($from, $until);

        CarbonImmutable::setTestNow('2026-01-01 12:00:00');

        $this->vault()->temporaryRole($user, $role, $tenant->getKey(), $grant);

        $resolver = $this->app->make(DatabasePermissionResolver::class);
        $ctx = new AuthorizationContext($user, $tenant->getKey(), null, null, null, 'testing');

        self::assertTrue($resolver->authorize($ctx, 'temp.act', null));

        CarbonImmutable::setTestNow('2026-01-03 00:00:00');

        self::assertFalse($resolver->authorize($ctx, 'temp.act', null));

        CarbonImmutable::setTestNow();
    }
}

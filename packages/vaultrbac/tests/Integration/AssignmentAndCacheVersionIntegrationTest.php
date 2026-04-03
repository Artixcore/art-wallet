<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Integration;

use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;

/**
 * @group integration
 */
final class AssignmentAndCacheVersionIntegrationTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_assign_role_bumps_assignment_version_for_subject(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'member');

        /** @var PermissionCacheVersionRepository $versions */
        $versions = $this->app->make(PermissionCacheVersionRepository::class);
        $scope = (string) config('vaultrbac.cache_admin.assignment_subject_type');

        self::assertSame(0, $versions->getVersion($tenant->getKey(), $scope, '', (int) $user->getKey()));

        $this->vault()->assignRole($user, $role, $tenant->getKey());

        self::assertSame(1, $versions->getVersion($tenant->getKey(), $scope, '', (int) $user->getKey()));
    }
}

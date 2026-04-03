<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Concurrency;

use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;

/**
 * Single-process monotonicity checks (not multi-process races).
 *
 * @group concurrency
 */
final class CacheVersionBumpConcurrencyTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_sequential_bumps_are_monotonic(): void
    {
        $tenant = $this->createTenant();

        /** @var PermissionCacheVersionRepository $versions */
        $versions = $this->app->make(PermissionCacheVersionRepository::class);
        $scope = (string) config('vaultrbac.freshness.scope');

        $a = $versions->bump($tenant->getKey(), $scope);
        $b = $versions->bump($tenant->getKey(), $scope);
        $c = $versions->bump($tenant->getKey(), $scope);

        self::assertLessThan($c, $b, 'later bump version must exceed earlier');
        self::assertLessThanOrEqual($b, $a, 'first bump must be at least initial version');
    }
}

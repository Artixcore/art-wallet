<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Integration;

use Artwallet\VaultRbac\Api\Dto\CacheWarmTarget;
use Artwallet\VaultRbac\Contracts\PermissionCacheAdminInterface;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * @group integration
 */
final class PermissionCacheAdminIntegrationTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_warm_then_flush_bumps_version_and_updates_warm_snapshot(): void
    {
        config([
            'vaultrbac.cache.prefix' => 'vrb_itest',
            'cache.default' => 'array',
        ]);

        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        /** @var PermissionCacheAdminInterface $admin */
        $admin = $this->app->make(PermissionCacheAdminInterface::class);
        $scope = (string) config('vaultrbac.freshness.scope');

        $warm = $admin->warm(new CacheWarmTarget(tenantId: $tenant->getKey(), userId: null, allTenants: false));
        self::assertTrue($warm->success);

        $prefix = (string) config('vaultrbac.cache.prefix');
        $warmKey = $prefix.':warm:tenant:'.(string) $tenant->getKey().':'.$scope;
        $v1 = Cache::get($warmKey);
        self::assertNotNull($v1);

        $flush = $admin->flush(new CacheWarmTarget(tenantId: $tenant->getKey(), userId: null, allTenants: false), true);
        self::assertTrue($flush->success);

        $versions = $this->app->make(\Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository::class);
        $vDb = $versions->getVersion($tenant->getKey(), $scope);
        self::assertGreaterThan((int) $v1, $vDb);
    }
}

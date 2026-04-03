<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Unit;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Contracts\SuperUserGuard;
use Artwallet\VaultRbac\Resolvers\VersionedCachingPermissionResolver;
use Artwallet\VaultRbac\Security\NullSuperUserGuard;
use Artwallet\VaultRbac\Tests\Fixtures\User;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use RuntimeException;

final class VersionedCachingPermissionResolverTest extends TestCase
{
    private function ctx(): AuthorizationContext
    {
        $user = new User;
        $user->id = 5;

        return new AuthorizationContext($user, 10, null, null, null, 'testing');
    }

    public function test_second_call_uses_cache_without_invoking_inner_again(): void
    {
        config([
            'vaultrbac.cache.decisions_enabled' => true,
            'vaultrbac.cache.decision_ttl_seconds' => 600,
            'vaultrbac.cache.prefix' => 'vrb_test',
            'vaultrbac.freshness.scope' => 'tenant',
            'vaultrbac.cache_admin.assignment_subject_type' => 'assignment',
            'vaultrbac.freshness.strict_version_read' => false,
            'vaultrbac.cache.fail_closed_on_cache_error' => false,
        ]);

        $versions = new class implements PermissionCacheVersionRepository {
            public function getVersion(
                string|int $tenantId,
                string $scope,
                string $subjectType = '',
                int $subjectId = 0,
            ): int {
                return $scope === 'tenant' ? 7 : 3;
            }

            public function bump(
                string|int $tenantId,
                string $scope,
                string $subjectType = '',
                int $subjectId = 0,
            ): int {
                return 1;
            }
        };

        $inner = $this->createMock(PermissionResolverInterface::class);
        $inner->expects(self::once())->method('authorize')->willReturn(true);

        $resolver = new VersionedCachingPermissionResolver(
            $inner,
            new CacheRepository(new ArrayStore),
            $versions,
            $this->app['config'],
            new NullSuperUserGuard,
            $this->app->make('log'),
        );

        $c = $this->ctx();
        self::assertTrue($resolver->authorize($c, 'a.b', null));
        self::assertTrue($resolver->authorize($c, 'a.b', null));
    }

    public function test_stale_catalog_version_in_payload_triggers_recompute(): void
    {
        config([
            'vaultrbac.cache.decisions_enabled' => true,
            'vaultrbac.cache.decision_ttl_seconds' => 600,
            'vaultrbac.cache.prefix' => 'vrb_test_stale',
            'vaultrbac.freshness.scope' => 'tenant',
            'vaultrbac.cache_admin.assignment_subject_type' => 'assignment',
            'vaultrbac.freshness.strict_version_read' => false,
            'vaultrbac.cache.fail_closed_on_cache_error' => false,
        ]);

        $versions = new class implements PermissionCacheVersionRepository {
            private int $catalog = 1;

            public function getVersion(
                string|int $tenantId,
                string $scope,
                string $subjectType = '',
                int $subjectId = 0,
            ): int {
                if ($scope === 'tenant') {
                    return $this->catalog;
                }

                return 0;
            }

            public function bump(
                string|int $tenantId,
                string $scope,
                string $subjectType = '',
                int $subjectId = 0,
            ): int {
                return 1;
            }

            public function bumpCatalog(): void
            {
                $this->catalog++;
            }
        };

        $inner = $this->createMock(PermissionResolverInterface::class);
        $inner->expects(self::exactly(2))->method('authorize')->willReturnOnConsecutiveCalls(true, false);

        $cache = new CacheRepository(new ArrayStore);
        $resolver = new VersionedCachingPermissionResolver(
            $inner,
            $cache,
            $versions,
            $this->app['config'],
            new NullSuperUserGuard,
            $this->app->make('log'),
        );

        $c = $this->ctx();
        self::assertTrue($resolver->authorize($c, 'x.y', null));
        $versions->bumpCatalog();
        self::assertFalse($resolver->authorize($c, 'x.y', null));
    }

    public function test_version_read_failure_with_strict_mode_denies_without_calling_inner(): void
    {
        config([
            'vaultrbac.cache.decisions_enabled' => true,
            'vaultrbac.freshness.scope' => 'tenant',
            'vaultrbac.cache_admin.assignment_subject_type' => 'assignment',
            'vaultrbac.freshness.strict_version_read' => true,
            'vaultrbac.cache.fail_closed_on_cache_error' => false,
        ]);

        $versions = new class implements PermissionCacheVersionRepository {
            public function getVersion(
                string|int $tenantId,
                string $scope,
                string $subjectType = '',
                int $subjectId = 0,
            ): int {
                throw new RuntimeException('db down');
            }

            public function bump(
                string|int $tenantId,
                string $scope,
                string $subjectType = '',
                int $subjectId = 0,
            ): int {
                return 1;
            }
        };

        $inner = $this->createMock(PermissionResolverInterface::class);
        $inner->expects(self::never())->method('authorize');

        $resolver = new VersionedCachingPermissionResolver(
            $inner,
            new CacheRepository(new ArrayStore),
            $versions,
            $this->app['config'],
            new NullSuperUserGuard,
            $this->app->make('log'),
        );

        self::assertFalse($resolver->authorize($this->ctx(), 'z', null));
    }

    public function test_cache_get_exception_with_fail_closed_denies(): void
    {
        config([
            'vaultrbac.cache.decisions_enabled' => true,
            'vaultrbac.freshness.scope' => 'tenant',
            'vaultrbac.cache_admin.assignment_subject_type' => 'assignment',
            'vaultrbac.freshness.strict_version_read' => false,
            'vaultrbac.cache.fail_closed_on_cache_error' => true,
        ]);

        $versions = new class implements PermissionCacheVersionRepository {
            public function getVersion(
                string|int $tenantId,
                string $scope,
                string $subjectType = '',
                int $subjectId = 0,
            ): int {
                return $scope === 'tenant' ? 1 : 0;
            }

            public function bump(
                string|int $tenantId,
                string $scope,
                string $subjectType = '',
                int $subjectId = 0,
            ): int {
                return 1;
            }
        };

        $inner = $this->createMock(PermissionResolverInterface::class);
        $inner->expects(self::never())->method('authorize');

        $cache = $this->createMock(CacheRepository::class);
        $cache->method('get')->willThrowException(new RuntimeException('redis unavailable'));

        $resolver = new VersionedCachingPermissionResolver(
            $inner,
            $cache,
            $versions,
            $this->app['config'],
            new NullSuperUserGuard,
            $this->app->make('log'),
        );

        self::assertFalse($resolver->authorize($this->ctx(), 'k', null));
    }
}

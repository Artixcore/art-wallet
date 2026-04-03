<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Security;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Resolvers\VersionedCachingPermissionResolver;
use Artwallet\VaultRbac\Security\NullSuperUserGuard;
use Artwallet\VaultRbac\Tests\Fixtures\User;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;

/**
 * @group security
 */
final class StaleDecisionCacheSecurityTest extends TestCase
{
    public function test_version_bump_prevents_stale_allow_from_prior_catalog_version(): void
    {
        config([
            'vaultrbac.cache.decisions_enabled' => true,
            'vaultrbac.cache.decision_ttl_seconds' => 3600,
            'vaultrbac.cache.prefix' => 'vrb_sec',
            'vaultrbac.freshness.scope' => 'tenant',
            'vaultrbac.cache_admin.assignment_subject_type' => 'assignment',
            'vaultrbac.freshness.strict_version_read' => false,
            'vaultrbac.cache.fail_closed_on_cache_error' => false,
        ]);

        $user = new User;
        $user->id = 2;
        $ctx = new AuthorizationContext($user, 99, null, null, null, 'testing');

        $versions = new class implements PermissionCacheVersionRepository {
            private int $catalog = 1;

            public function getVersion(
                string|int $tenantId,
                string $scope,
                string $subjectType = '',
                int $subjectId = 0,
            ): int {
                return $scope === 'tenant' ? $this->catalog : 0;
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

        $resolver = new VersionedCachingPermissionResolver(
            $inner,
            new CacheRepository(new ArrayStore),
            $versions,
            $this->app['config'],
            new NullSuperUserGuard,
            $this->app->make('log'),
        );

        self::assertTrue($resolver->authorize($ctx, 'risk.op', null));
        $versions->bumpCatalog();
        self::assertFalse($resolver->authorize($ctx, 'risk.op', null));
    }
}

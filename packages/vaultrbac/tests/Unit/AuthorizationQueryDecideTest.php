<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Unit;

use Artwallet\VaultRbac\Api\AuthorizationQuery;
use Artwallet\VaultRbac\Api\Dto\PermissionDecision;
use Artwallet\VaultRbac\Api\PermissionDenialReason;
use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Tests\Fixtures\User;
use Artwallet\VaultRbac\Tests\TestCase;
use RuntimeException;

final class AuthorizationQueryDecideTest extends TestCase
{
    public function test_empty_ability_denies_with_empty_ability_reason(): void
    {
        $q = $this->makeQuery(resolverAllows: false);
        $d = $q->forUser($this->makeUser())->inTenant(1)->decide('  ');

        self::assertInstanceOf(PermissionDecision::class, $d);
        self::assertFalse($d->granted);
        self::assertSame(PermissionDenialReason::EmptyAbility, $d->reason);
    }

    public function test_guest_user_denies_before_resolver(): void
    {
        $resolver = $this->createMock(PermissionResolverInterface::class);
        $resolver->expects(self::never())->method('authorize');

        $q = $this->makeQueryWithResolver($resolver);
        $d = $q->forUser(null)->inTenant(1)->decide('posts.view');

        self::assertSame(PermissionDenialReason::GuestUser, $d->reason);
    }

    public function test_strict_tenant_missing_denies(): void
    {
        config(['vaultrbac.require_tenant_context' => true]);

        $resolver = $this->createMock(PermissionResolverInterface::class);
        $resolver->expects(self::never())->method('authorize');

        $q = $this->makeQueryWithResolver($resolver);
        $d = $q->forUser($this->makeUser())
            ->inTenant(null)
            ->withStrictTenantRequirement()
            ->decide('x');

        self::assertSame(PermissionDenialReason::StrictTenantRequired, $d->reason);
    }

    public function test_version_read_failure_strict_denies_with_version_read_failed(): void
    {
        config([
            'vaultrbac.require_tenant_context' => true,
            'vaultrbac.freshness.scope' => 'tenant',
            'vaultrbac.freshness.strict_version_read' => true,
        ]);

        $versions = $this->createMock(PermissionCacheVersionRepository::class);
        $versions->method('getVersion')->willThrowException(new RuntimeException('db'));

        $resolver = $this->createMock(PermissionResolverInterface::class);
        $resolver->expects(self::never())->method('authorize');

        $q = $this->makeQueryWithResolver($resolver, $versions);
        $d = $q->forUser($this->makeUser())->inTenant(1)->decide('y');

        self::assertSame(PermissionDenialReason::VersionReadFailed, $d->reason);
        self::assertFalse($d->permissionCacheVersionResolved);
    }

    public function test_resolver_denied_surfaces_resolver_denied_reason(): void
    {
        $resolver = $this->createMock(PermissionResolverInterface::class);
        $resolver->method('authorize')->willReturn(false);

        $q = $this->makeQueryWithResolver($resolver);
        $d = $q->forUser($this->makeUser())->inTenant(1)->decide('z');

        self::assertSame(PermissionDenialReason::ResolverDenied, $d->reason);
    }

    private function makeQuery(bool $resolverAllows): AuthorizationQuery
    {
        $resolver = $this->createMock(PermissionResolverInterface::class);
        $resolver->method('authorize')->willReturn($resolverAllows);

        return $this->makeQueryWithResolver($resolver);
    }

    private function makeQueryWithResolver(
        PermissionResolverInterface $resolver,
        ?PermissionCacheVersionRepository $versions = null,
    ): AuthorizationQuery {
        $factory = $this->createMock(AuthorizationContextFactory::class);
        $factory->method('makeFor')->willReturnCallback(function (?\Illuminate\Contracts\Auth\Authenticatable $user): AuthorizationContext {
            return new AuthorizationContext($user, null, null, null, null, 'testing');
        });

        return AuthorizationQuery::make(
            $resolver,
            $factory,
            $this->app['config'],
            $versions,
        );
    }

    private function makeUser(): User
    {
        $u = new User;
        $u->id = 99;

        return $u;
    }
}

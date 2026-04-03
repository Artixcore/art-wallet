<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Security;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Resolvers\SafePermissionResolver;
use Artwallet\VaultRbac\Tests\Fixtures\User;
use Artwallet\VaultRbac\Tests\TestCase;
use RuntimeException;

/**
 * @group security
 */
final class SafeResolverSecurityTest extends TestCase
{
    public function test_inner_exception_yields_deny_not_allow(): void
    {
        $inner = new class implements PermissionResolverInterface {
            public function authorize(
                AuthorizationContext $context,
                string|\Stringable $ability,
                ?object $resource = null,
            ): bool {
                throw new RuntimeException('resolver fault');
            }
        };

        $resolver = new SafePermissionResolver(
            $inner,
            $this->app['config'],
            $this->app->make('log'),
        );

        $user = new User;
        $user->id = 1;
        $ctx = new AuthorizationContext($user, 1, null, null, null, 'testing');

        self::assertFalse($resolver->authorize($ctx, 'any', null));
    }
}

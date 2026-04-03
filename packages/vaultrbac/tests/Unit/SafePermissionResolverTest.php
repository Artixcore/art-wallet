<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Unit;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Resolvers\SafePermissionResolver;
use Artwallet\VaultRbac\Tests\Fixtures\User;
use Artwallet\VaultRbac\Tests\TestCase;
use RuntimeException;

final class SafePermissionResolverTest extends TestCase
{
    public function test_throwable_from_inner_results_in_deny(): void
    {
        $inner = new class implements PermissionResolverInterface {
            public function authorize(
                AuthorizationContext $context,
                string|\Stringable $ability,
                ?object $resource = null,
            ): bool {
                throw new RuntimeException('simulated failure');
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

        self::assertFalse($resolver->authorize($ctx, 'posts.edit', null));
    }
}

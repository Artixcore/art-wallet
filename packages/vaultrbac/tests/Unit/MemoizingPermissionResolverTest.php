<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Unit;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Resolvers\MemoizingPermissionResolver;
use Artwallet\VaultRbac\Support\AuthorizationRequestMemo;
use Artwallet\VaultRbac\Tests\Fixtures\User;
use Artwallet\VaultRbac\Tests\TestCase;

final class MemoizingPermissionResolverTest extends TestCase
{
    public function test_inner_invoked_once_per_memo_key_within_same_memo_instance(): void
    {
        $inner = $this->createMock(PermissionResolverInterface::class);
        $inner->expects(self::once())->method('authorize')->willReturn(true);

        $memo = new AuthorizationRequestMemo;
        $resolver = new MemoizingPermissionResolver($inner, $memo);

        $user = new User;
        $user->id = 1;
        $ctx = new AuthorizationContext($user, 1, null, null, null, 'testing');

        self::assertTrue($resolver->authorize($ctx, 'same.ability', null));
        self::assertTrue($resolver->authorize($ctx, 'same.ability', null));
    }

    public function test_flush_allows_second_resolution(): void
    {
        $inner = $this->createMock(PermissionResolverInterface::class);
        $inner->expects(self::exactly(2))->method('authorize')->willReturn(false);

        $memo = new AuthorizationRequestMemo;
        $resolver = new MemoizingPermissionResolver($inner, $memo);

        $user = new User;
        $user->id = 1;
        $ctx = new AuthorizationContext($user, 1, null, null, null, 'testing');

        self::assertFalse($resolver->authorize($ctx, 'a', null));
        $memo->flush();
        self::assertFalse($resolver->authorize($ctx, 'a', null));
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Resolvers;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Support\AuthorizationRequestMemo;

/**
 * Delegates to the inner resolver, memoizing outcomes for the current request.
 */
final class MemoizingPermissionResolver implements PermissionResolverInterface
{
    public function __construct(
        private readonly PermissionResolverInterface $inner,
        private readonly AuthorizationRequestMemo $memo,
    ) {}

    public function authorize(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        $name = trim((string) $ability);

        return $this->memo->remember(
            $context,
            $name,
            $resource,
            fn (): bool => $this->inner->authorize($context, $name, $resource),
        );
    }
}

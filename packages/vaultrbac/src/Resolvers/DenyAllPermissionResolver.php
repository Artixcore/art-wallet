<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Resolvers;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;

/**
 * Phase 1 safe default: deny every authorization until the engine is installed.
 */
final class DenyAllPermissionResolver implements PermissionResolverInterface
{
    public function authorize(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return false;
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Context\AuthorizationContext;

/**
 * Core authorization decision. Implementations must be deterministic given
 * context + ability + resource and must fail closed on internal errors
 * (log + deny), unless explicitly documented otherwise.
 */
interface PermissionResolverInterface
{
    /**
     * @param  string|\Stringable  $ability  Machine ability / permission name (e.g. posts.update).
     */
    public function authorize(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool;
}

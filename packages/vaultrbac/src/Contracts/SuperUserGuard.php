<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Context\AuthorizationContext;

/**
 * Explicit break-glass / super-user path. Default implementation must return
 * false. Never implement as “user id === 1” without additional controls.
 */
interface SuperUserGuard
{
    public function allowsPrivilegedBypass(AuthorizationContext $context): bool;
}

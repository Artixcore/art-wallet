<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Context\AuthorizationContext;

/**
 * Builds a frozen {@see AuthorizationContext} for the current runtime
 * (HTTP, queue, console). Implementations may read auth(), session, and
 * {@see TenantResolver}.
 */
interface AuthorizationContextFactory
{
    public function make(): AuthorizationContext;
}

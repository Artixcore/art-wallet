<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Builds a frozen {@see AuthorizationContext} for the current runtime
 * (HTTP, queue, console). Implementations may read auth(), session, and
 * {@see TenantResolver}.
 */
interface AuthorizationContextFactory
{
    public function make(): AuthorizationContext;

    /**
     * Build context for an explicit user (Gates, queued jobs, impersonation).
     * Tenant/team/session/device still come from resolvers and current request when bound.
     */
    public function makeFor(?Authenticatable $user): AuthorizationContext;
}

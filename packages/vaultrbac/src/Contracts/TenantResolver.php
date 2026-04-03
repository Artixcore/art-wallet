<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Exceptions\TenantResolutionException;

/**
 * Resolves the active tenant identifier for the current request / job context.
 *
 * Must never throw for “no tenant”; return null when the application is in
 * global (non-tenant) mode. Throw {@see TenantResolutionException}
 * only when tenant context is required but cannot be determined safely.
 */
interface TenantResolver
{
    /**
     * @return string|int|null Opaque tenant key (UUID, bigint, etc.)
     */
    public function resolve(): string|int|null;
}

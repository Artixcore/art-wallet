<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Verifies that a user may act inside a tenant (used by tenant middleware).
 */
interface TenantMembershipVerifier
{
    public function verify(Model $user, string|int $tenantId): bool;
}

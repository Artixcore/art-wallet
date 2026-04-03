<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tenancy;

use Artwallet\VaultRbac\Contracts\TenantMembershipVerifier;
use Artwallet\VaultRbac\Models\ModelPermission;
use Artwallet\VaultRbac\Models\ModelRole;
use Illuminate\Database\Eloquent\Model;

/**
 * Grants membership when the user has any VaultRBAC assignment rows for the tenant.
 * Replace with your own verifier when you store membership outside vrb_* tables.
 */
final class AssignmentBackedTenantMembershipVerifier implements TenantMembershipVerifier
{
    public function verify(Model $user, string|int $tenantId): bool
    {
        $type = $user->getMorphClass();
        $id = $user->getKey();

        if (ModelRole::query()
            ->where('tenant_id', $tenantId)
            ->where('model_type', $type)
            ->where('model_id', $id)
            ->exists()) {
            return true;
        }

        return ModelPermission::query()
            ->where('tenant_id', $tenantId)
            ->where('model_type', $type)
            ->where('model_id', $id)
            ->exists();
    }
}

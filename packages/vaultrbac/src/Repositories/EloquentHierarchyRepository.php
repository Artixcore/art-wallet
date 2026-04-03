<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\HierarchyRepository;
use Artwallet\VaultRbac\Models\PermissionInheritance;
use Artwallet\VaultRbac\Models\RoleHierarchy;
use Illuminate\Support\Collection;

final class EloquentHierarchyRepository implements HierarchyRepository
{
    public function roleHierarchyEdgesForTenant(string|int $tenantId): Collection
    {
        return RoleHierarchy::query()
            ->where(function ($q) use ($tenantId): void {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderBy('id')
            ->get();
    }

    public function permissionInheritanceEdgesForTenant(string|int $tenantId): Collection
    {
        return PermissionInheritance::query()
            ->where(function ($q) use ($tenantId): void {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderBy('id')
            ->get();
    }

    public function directParentRoleIds(int $childRoleId, string|int $tenantId): array
    {
        return RoleHierarchy::query()
            ->where('child_role_id', $childRoleId)
            ->where(function ($q) use ($tenantId): void {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->pluck('parent_role_id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}

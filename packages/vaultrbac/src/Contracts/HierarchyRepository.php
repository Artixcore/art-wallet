<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Models\PermissionInheritance;
use Artwallet\VaultRbac\Models\RoleHierarchy;
use Illuminate\Support\Collection;

interface HierarchyRepository
{
    /**
     * @return Collection<int, RoleHierarchy>
     */
    public function roleHierarchyEdgesForTenant(string|int $tenantId): Collection;

    /**
     * @return Collection<int, PermissionInheritance>
     */
    public function permissionInheritanceEdgesForTenant(string|int $tenantId): Collection;

    /**
     * @return list<int>
     */
    public function directParentRoleIds(int $childRoleId, string|int $tenantId): array;
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Hierarchy;

use Artwallet\VaultRbac\Contracts\RoleHierarchyProvider;

final class NullRoleHierarchyProvider implements RoleHierarchyProvider
{
    public function directParents(string|int $childRoleId, string|int|null $tenantId): array
    {
        return [];
    }

    public function ancestors(string|int $childRoleId, string|int|null $tenantId): array
    {
        return [];
    }
}

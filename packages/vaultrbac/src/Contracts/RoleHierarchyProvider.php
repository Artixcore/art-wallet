<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

/**
 * Supplies role parent/ancestor relationships for hierarchical RBAC.
 * Identifiers must match the role primary keys used in assignments.
 */
interface RoleHierarchyProvider
{
    /**
     * @return list<string|int> Direct parent role ids for $childRoleId.
     */
    public function directParents(string|int $childRoleId, string|int|null $tenantId): array;

    /**
     * @return list<string|int> Transitive ancestors, excluding $childRoleId itself.
     *                          Order is nearest-parent-first or breadth-first; callers must not rely on order for correctness.
     */
    public function ancestors(string|int $childRoleId, string|int|null $tenantId): array;
}

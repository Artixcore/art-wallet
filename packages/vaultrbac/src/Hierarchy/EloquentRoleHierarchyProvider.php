<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Hierarchy;

use Artwallet\VaultRbac\Contracts\RoleHierarchyProvider;
use Artwallet\VaultRbac\Exceptions\ConfigurationException;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\RoleHierarchy;

final class EloquentRoleHierarchyProvider implements RoleHierarchyProvider
{
    public function directParents(string|int $childRoleId, string|int|null $tenantId): array
    {
        $parents = RoleHierarchy::query()
            ->where('child_role_id', $childRoleId)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id');
                if ($tenantId !== null) {
                    $query->orWhere('tenant_id', $tenantId);
                }
            })
            ->pluck('parent_role_id')
            ->all();

        $roleClass = $this->roleModelClass();
        $role = $roleClass::query()->find($childRoleId);
        if ($role instanceof Role && $role->parent_role_id !== null) {
            $parents[] = $role->parent_role_id;
        }

        return array_values(array_unique(array_map(static fn ($id): string|int => $id, $parents)));
    }

    public function ancestors(string|int $childRoleId, string|int|null $tenantId): array
    {
        $maxNodes = (int) config('vaultrbac.hierarchy.max_expanded_nodes', 256);
        $seen = [(string) $childRoleId => true];
        $queue = [$childRoleId];
        $ancestors = [];

        while ($queue !== [] && count($seen) <= $maxNodes) {
            $id = array_shift($queue);
            foreach ($this->directParents($id, $tenantId) as $parentId) {
                $key = (string) $parentId;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $ancestors[] = $parentId;
                $queue[] = $parentId;
            }
        }

        return $ancestors;
    }

    /**
     * @return class-string<Role>
     */
    private function roleModelClass(): string
    {
        $class = config('vaultrbac.models.role');

        if (! is_string($class) || ! class_exists($class)) {
            throw new ConfigurationException('Invalid vaultrbac.models.role configuration.');
        }

        return $class;
    }
}

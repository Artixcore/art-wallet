<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Models\ModelPermission;
use Artwallet\VaultRbac\Models\ModelRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Read-only queries backing {@see PermissionResolverInterface} (Phase 3).
 */
interface AuthorizationRepository
{
    /**
     * Permission primary keys for an exact ability name visible to the tenant
     * (global rows with null tenant_id plus tenant-owned definitions).
     *
     * @return list<int|string>
     */
    public function permissionIdsForAbility(string $ability, string|int $tenantId): array;

    /**
     * Active direct permission assignments for the subject model.
     *
     * @return Collection<int, ModelPermission>
     */
    public function directModelPermissions(
        Model $model,
        string|int $tenantId,
        string|int|null $teamId,
    ): Collection;

    /**
     * Active role assignments for the subject model (rows only; role state validated separately).
     *
     * @return Collection<int, ModelRole>
     */
    public function modelRoles(
        Model $model,
        string|int $tenantId,
        string|int|null $teamId,
    ): Collection;

    /**
     * Permission IDs granted to roles via the role_permission pivot for the tenant scope.
     *
     * @param  list<int|string>  $roleIds
     * @return list<int|string>
     */
    public function permissionIdsGrantedToRoles(array $roleIds, string|int $tenantId): array;

    /**
     * Whether the user has an active assignment to a role with the given name
     * (direct assignment only; hierarchy not expanded).
     */
    public function userHasActiveRoleNamed(
        Model $user,
        string $roleName,
        string|int $tenantId,
        string|int|null $teamId,
    ): bool;
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Database\Eloquent\Model;

interface AssignmentServiceInterface
{
    public function assignRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): void;

    public function revokeRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
    ): void;

    /**
     * @param  list<Role|string|int>  $roles
     */
    public function syncRoles(
        Model $model,
        array $roles,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): void;

    public function givePermissionTo(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        string $effect = 'allow',
        string|int|null $assignedBy = null,
    ): void;

    public function revokePermissionTo(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        ?string $effect = null,
    ): void;
}

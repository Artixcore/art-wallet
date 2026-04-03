<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Traits;

use Artwallet\VaultRbac\Contracts\AssignmentServiceInterface;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Support\Facades\Auth;

trait HasVaultRoles
{
    public function assignRole(
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): void {
        $assignedBy ??= Auth::id();

        app(AssignmentServiceInterface::class)->assignRole($this, $role, $tenantId, $teamId, $assignedBy);
    }

    public function revokeRole(
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
    ): void {
        app(AssignmentServiceInterface::class)->revokeRole($this, $role, $tenantId, $teamId);
    }

    /**
     * @param  list<Role|string|int>  $roles
     */
    public function syncRoles(
        array $roles,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): void {
        $assignedBy ??= Auth::id();

        app(AssignmentServiceInterface::class)->syncRoles($this, $roles, $tenantId, $teamId, $assignedBy);
    }

    public function givePermissionTo(
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        string $effect = 'allow',
        string|int|null $assignedBy = null,
    ): void {
        $assignedBy ??= Auth::id();

        app(AssignmentServiceInterface::class)->givePermissionTo($this, $permission, $tenantId, $teamId, $effect, $assignedBy);
    }

    public function revokePermissionTo(
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        ?string $effect = null,
    ): void {
        app(AssignmentServiceInterface::class)->revokePermissionTo($this, $permission, $tenantId, $teamId, $effect);
    }
}

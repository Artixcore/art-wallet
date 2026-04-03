<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Services;

use Artwallet\VaultRbac\Contracts\AssignmentServiceInterface;
use Artwallet\VaultRbac\Contracts\CacheInvalidator;
use Artwallet\VaultRbac\Events\PermissionGranted;
use Artwallet\VaultRbac\Events\PermissionRevoked;
use Artwallet\VaultRbac\Events\RoleAssigned;
use Artwallet\VaultRbac\Events\RoleRevoked;
use Artwallet\VaultRbac\Exceptions\InvalidAssignmentException;
use Artwallet\VaultRbac\Models\ModelPermission;
use Artwallet\VaultRbac\Models\ModelRole;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class AssignmentService implements AssignmentServiceInterface
{
    public function __construct(
        private readonly CacheInvalidator $cacheInvalidator,
    ) {}

    public function assignRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): void {
        $roleModel = $this->resolveRole($role, $tenantId);

        DB::transaction(function () use ($model, $roleModel, $tenantId, $teamId, $assignedBy): void {
            $this->lockAssignmentsFor($model, $tenantId, $teamId, true);

            $assignment = ModelRole::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'team_key' => $teamId !== null ? (int) $teamId : 0,
                    'role_id' => $roleModel->getKey(),
                    'model_type' => $model->getMorphClass(),
                    'model_id' => $model->getKey(),
                ],
                [
                    'team_id' => $teamId,
                    'assigned_by' => $assignedBy,
                    'assigned_at' => now(),
                ],
            );

            if ($assignment->wasRecentlyCreated) {
                Event::dispatch(new RoleAssigned($model, $roleModel, $tenantId, $teamId, $assignment));
            }
        });

        $this->bumpCache($tenantId, $model);
    }

    public function revokeRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
    ): void {
        $roleModel = $this->resolveRole($role, $tenantId);

        DB::transaction(function () use ($model, $roleModel, $tenantId, $teamId): void {
            $this->lockAssignmentsFor($model, $tenantId, $teamId, true);

            $query = ModelRole::query()
                ->where('tenant_id', $tenantId)
                ->where('role_id', $roleModel->getKey())
                ->where('model_type', $model->getMorphClass())
                ->where('model_id', $model->getKey())
                ->where('team_key', $teamId !== null ? (int) $teamId : 0);

            $deleted = $query->delete();

            if ($deleted > 0) {
                Event::dispatch(new RoleRevoked($model, $roleModel, $tenantId, $teamId));
            }
        });

        $this->bumpCache($tenantId, $model);
    }

    public function syncRoles(
        Model $model,
        array $roles,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): void {
        $resolved = [];
        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role, $tenantId);
            $resolved[(string) $roleModel->getKey()] = $roleModel;
        }

        $wantedIds = array_keys($resolved);

        DB::transaction(function () use ($model, $tenantId, $teamId, $assignedBy, $resolved, $wantedIds): void {
            $this->lockAssignmentsFor($model, $tenantId, $teamId, true);

            $query = ModelRole::query()
                ->where('tenant_id', $tenantId)
                ->where('model_type', $model->getMorphClass())
                ->where('model_id', $model->getKey())
                ->where('team_key', $teamId !== null ? (int) $teamId : 0);

            $existing = $query->get();
            $existingIds = $existing->pluck('role_id')->map(static fn ($id): string => (string) $id)->all();

            foreach ($existing as $row) {
                $rid = (string) $row->role_id;
                if (in_array($rid, $wantedIds, true)) {
                    continue;
                }

                $removedRole = $this->roleClass()::query()->find($row->role_id);
                $row->delete();
                if ($removedRole instanceof Role) {
                    Event::dispatch(new RoleRevoked($model, $removedRole, $tenantId, $teamId));
                }
            }

            foreach ($wantedIds as $id) {
                if (in_array($id, $existingIds, true)) {
                    continue;
                }
                $roleModel = $resolved[$id];
                $assignment = ModelRole::query()->create([
                    'tenant_id' => $tenantId,
                    'team_id' => $teamId,
                    'team_key' => $teamId !== null ? (int) $teamId : 0,
                    'role_id' => $roleModel->getKey(),
                    'model_type' => $model->getMorphClass(),
                    'model_id' => $model->getKey(),
                    'assigned_by' => $assignedBy,
                    'assigned_at' => now(),
                ]);
                Event::dispatch(new RoleAssigned($model, $roleModel, $tenantId, $teamId, $assignment));
            }
        });

        $this->bumpCache($tenantId, $model);
    }

    public function givePermissionTo(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        string $effect = 'allow',
        string|int|null $assignedBy = null,
    ): void {
        if (! in_array($effect, ['allow', 'deny'], true)) {
            throw new InvalidAssignmentException('Permission effect must be "allow" or "deny".');
        }

        $permissionModel = $this->resolvePermission($permission, $tenantId);

        DB::transaction(function () use ($model, $permissionModel, $tenantId, $teamId, $effect, $assignedBy): void {
            $this->lockAssignmentsFor($model, $tenantId, $teamId, false);

            $assignment = ModelPermission::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'team_key' => $teamId !== null ? (int) $teamId : 0,
                    'permission_id' => $permissionModel->getKey(),
                    'model_type' => $model->getMorphClass(),
                    'model_id' => $model->getKey(),
                    'effect' => $effect,
                ],
                [
                    'team_id' => $teamId,
                    'assigned_by' => $assignedBy,
                    'assigned_at' => now(),
                ],
            );

            if ($assignment->wasRecentlyCreated) {
                Event::dispatch(new PermissionGranted($model, $permissionModel, $tenantId, $teamId, $effect, $assignment));
            }
        });

        $this->bumpCache($tenantId, $model);
    }

    public function revokePermissionTo(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        ?string $effect = null,
    ): void {
        $permissionModel = $this->resolvePermission($permission, $tenantId);

        DB::transaction(function () use ($model, $permissionModel, $tenantId, $teamId, $effect): void {
            $this->lockAssignmentsFor($model, $tenantId, $teamId, false);

            $query = ModelPermission::query()
                ->where('tenant_id', $tenantId)
                ->where('permission_id', $permissionModel->getKey())
                ->where('model_type', $model->getMorphClass())
                ->where('model_id', $model->getKey())
                ->where('team_key', $teamId !== null ? (int) $teamId : 0);

            if ($effect !== null) {
                $query->where('effect', $effect);
            }

            $deleted = $query->delete();
            if ($deleted > 0) {
                Event::dispatch(new PermissionRevoked($model, $permissionModel, $tenantId, $teamId, $effect));
            }
        });

        $this->bumpCache($tenantId, $model);
    }

    private function lockAssignmentsFor(
        Model $model,
        string|int $tenantId,
        string|int|null $teamId,
        bool $roles,
    ): void {
        if ($roles) {
            ModelRole::query()
                ->where('tenant_id', $tenantId)
                ->where('model_type', $model->getMorphClass())
                ->where('model_id', $model->getKey())
                ->where('team_key', $teamId !== null ? (int) $teamId : 0)
                ->lockForUpdate()
                ->get();

            return;
        }

        ModelPermission::query()
            ->where('tenant_id', $tenantId)
            ->where('model_type', $model->getMorphClass())
            ->where('model_id', $model->getKey())
            ->where('team_key', $teamId !== null ? (int) $teamId : 0)
            ->lockForUpdate()
            ->get();
    }

    private function bumpCache(string|int $tenantId, Model $model): void
    {
        $this->cacheInvalidator->bumpAssignmentVersion($tenantId, $model->getKey());
    }

    private function resolveRole(Role|string|int $role, string|int $tenantId): Role
    {
        if ($role instanceof Role) {
            $this->assertRoleTenantScope($role, $tenantId);

            return $role;
        }

        $class = $this->roleClass();

        if (is_int($role) || (is_string($role) && ctype_digit($role))) {
            $found = $class::query()->findOrFail($role);
            $this->assertRoleTenantScope($found, $tenantId);

            return $found;
        }

        if (! is_string($role) || $role === '') {
            throw new InvalidAssignmentException('Role must be a Role model, numeric id, or non-empty name.');
        }

        $found = $class::query()
            ->where('name', $role)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 WHEN tenant_id IS NULL THEN 1 ELSE 2 END', [$tenantId])
            ->first();

        if (! $found instanceof Role) {
            throw new InvalidAssignmentException(sprintf('Role [%s] not found for tenant scope.', $role));
        }

        $this->assertRoleTenantScope($found, $tenantId);

        return $found;
    }

    private function resolvePermission(Permission|string|int $permission, string|int $tenantId): Permission
    {
        if ($permission instanceof Permission) {
            $this->assertPermissionTenantScope($permission, $tenantId);

            return $permission;
        }

        $class = $this->permissionClass();

        if (is_int($permission) || (is_string($permission) && ctype_digit($permission))) {
            $found = $class::query()->findOrFail($permission);
            $this->assertPermissionTenantScope($found, $tenantId);

            return $found;
        }

        if (! is_string($permission) || $permission === '') {
            throw new InvalidAssignmentException('Permission must be a Permission model, numeric id, or non-empty name.');
        }

        $found = $class::query()
            ->where('name', $permission)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 WHEN tenant_id IS NULL THEN 1 ELSE 2 END', [$tenantId])
            ->first();

        if (! $found instanceof Permission) {
            throw new InvalidAssignmentException(sprintf('Permission [%s] not found for tenant scope.', $permission));
        }

        $this->assertPermissionTenantScope($found, $tenantId);

        return $found;
    }

    private function assertRoleTenantScope(Role $role, string|int $tenantId): void
    {
        if ($role->tenant_id !== null && (string) $role->tenant_id !== (string) $tenantId) {
            throw new InvalidAssignmentException('Role belongs to a different tenant.');
        }
    }

    private function assertPermissionTenantScope(Permission $permission, string|int $tenantId): void
    {
        if ($permission->tenant_id !== null && (string) $permission->tenant_id !== (string) $tenantId) {
            throw new InvalidAssignmentException('Permission belongs to a different tenant.');
        }
    }

    /**
     * @return class-string<Role>
     */
    private function roleClass(): string
    {
        $class = config('vaultrbac.models.role');
        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidAssignmentException('Invalid vaultrbac.models.role configuration.');
        }

        return $class;
    }

    /**
     * @return class-string<Permission>
     */
    private function permissionClass(): string
    {
        $class = config('vaultrbac.models.permission');
        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidAssignmentException('Invalid vaultrbac.models.permission configuration.');
        }

        return $class;
    }
}

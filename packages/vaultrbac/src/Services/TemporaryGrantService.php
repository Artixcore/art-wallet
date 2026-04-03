<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Services;

use Artwallet\VaultRbac\Api\Dto\AssignmentResult;
use Artwallet\VaultRbac\Api\Dto\TemporaryGrantData;
use Artwallet\VaultRbac\Contracts\AssignmentServiceInterface;
use Artwallet\VaultRbac\Contracts\TemporaryGrantServiceInterface;
use Artwallet\VaultRbac\Models\ModelPermission;
use Artwallet\VaultRbac\Models\ModelRole;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class TemporaryGrantService implements TemporaryGrantServiceInterface
{
    public function __construct(
        private readonly AssignmentServiceInterface $assignments,
    ) {}

    public function grantTemporaryRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        TemporaryGrantData $grant,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): AssignmentResult {
        DB::transaction(function () use ($model, $role, $tenantId, $grant, $teamId, $assignedBy): void {
            $this->assignments->assignRole($model, $role, $tenantId, $teamId, $assignedBy);
            $roleModel = $this->resolveRoleAfterAssign($role, $tenantId);

            ModelRole::query()
                ->where('tenant_id', $tenantId)
                ->where('model_type', $model->getMorphClass())
                ->where('model_id', $model->getKey())
                ->where('team_key', $teamId !== null ? (int) $teamId : 0)
                ->where('role_id', $roleModel->getKey())
                ->update([
                    'expires_at' => $grant->validUntil,
                    'assigned_at' => $grant->validFrom,
                ]);
        });

        return AssignmentResult::forOperation(
            'grant_temporary_role',
            $tenantId,
            $model->getMorphClass(),
            $model->getKey(),
        );
    }

    public function grantTemporaryPermission(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        TemporaryGrantData $grant,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): AssignmentResult {
        DB::transaction(function () use ($model, $permission, $tenantId, $grant, $teamId, $assignedBy): void {
            $this->assignments->givePermissionTo($model, $permission, $tenantId, $teamId, 'allow', $assignedBy);
            $permModel = $this->resolvePermissionAfterAssign($permission, $tenantId);

            ModelPermission::query()
                ->where('tenant_id', $tenantId)
                ->where('model_type', $model->getMorphClass())
                ->where('model_id', $model->getKey())
                ->where('team_key', $teamId !== null ? (int) $teamId : 0)
                ->where('permission_id', $permModel->getKey())
                ->where('effect', 'allow')
                ->update([
                    'expires_at' => $grant->validUntil,
                    'assigned_at' => $grant->validFrom,
                ]);
        });

        return AssignmentResult::forOperation(
            'grant_temporary_permission',
            $tenantId,
            $model->getMorphClass(),
            $model->getKey(),
        );
    }

    private function resolveRoleAfterAssign(Role|string|int $role, string|int $tenantId): Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        $class = config('vaultrbac.models.role');
        if (! is_string($class) || ! class_exists($class)) {
            throw new \Artwallet\VaultRbac\Exceptions\InvalidAssignmentException('Invalid vaultrbac.models.role configuration.');
        }

        if (is_int($role) || (is_string($role) && ctype_digit($role))) {
            /** @var Role $found */
            $found = $class::query()->findOrFail($role);

            return $found;
        }

        /** @var Role $found */
        $found = $class::query()
            ->where('name', $role)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 WHEN tenant_id IS NULL THEN 1 ELSE 2 END', [$tenantId])
            ->firstOrFail();

        return $found;
    }

    private function resolvePermissionAfterAssign(Permission|string|int $permission, string|int $tenantId): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        $class = config('vaultrbac.models.permission');
        if (! is_string($class) || ! class_exists($class)) {
            throw new \Artwallet\VaultRbac\Exceptions\InvalidAssignmentException('Invalid vaultrbac.models.permission configuration.');
        }

        if (is_int($permission) || (is_string($permission) && ctype_digit($permission))) {
            /** @var Permission $found */
            $found = $class::query()->findOrFail($permission);

            return $found;
        }

        /** @var Permission $found */
        $found = $class::query()
            ->where('name', $permission)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 WHEN tenant_id IS NULL THEN 1 ELSE 2 END', [$tenantId])
            ->firstOrFail();

        return $found;
    }
}

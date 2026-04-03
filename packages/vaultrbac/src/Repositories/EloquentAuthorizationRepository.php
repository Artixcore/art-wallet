<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\AuthorizationRepository;
use Artwallet\VaultRbac\Database\VaultrbacTables;
use Artwallet\VaultRbac\Exceptions\ConfigurationException;
use Artwallet\VaultRbac\Models\ModelPermission;
use Artwallet\VaultRbac\Models\ModelRole;
use Artwallet\VaultRbac\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentAuthorizationRepository implements AuthorizationRepository
{
    public function permissionIdsForAbility(string $ability, string|int $tenantId): array
    {
        $class = $this->permissionModelClass();

        return $class::query()
            ->where('name', $ability)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->pluck('id')
            ->map(static fn ($id): string|int => $id)
            ->values()
            ->all();
    }

    public function directModelPermissions(
        Model $model,
        string|int $tenantId,
        string|int|null $teamId,
    ): Collection {
        return ModelPermission::query()
            ->where('model_type', $model->getMorphClass())
            ->where('model_id', $model->getKey())
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($teamId): void {
                if ($teamId === null) {
                    $query->whereNull('team_id');
                } else {
                    $query->where(function ($inner) use ($teamId): void {
                        $inner->whereNull('team_id')->orWhere('team_id', $teamId);
                    });
                }
            })
            ->whereNull('suspended_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('permission')
            ->get();
    }

    public function modelRoles(
        Model $model,
        string|int $tenantId,
        string|int|null $teamId,
    ): Collection {
        return ModelRole::query()
            ->where('model_type', $model->getMorphClass())
            ->where('model_id', $model->getKey())
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($teamId): void {
                if ($teamId === null) {
                    $query->whereNull('team_id');
                } else {
                    $query->where(function ($inner) use ($teamId): void {
                        $inner->whereNull('team_id')->orWhere('team_id', $teamId);
                    });
                }
            })
            ->whereNull('suspended_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('role')
            ->get();
    }

    public function permissionIdsGrantedToRoles(array $roleIds, string|int $tenantId): array
    {
        if ($roleIds === []) {
            return [];
        }

        $pivot = VaultrbacTables::name('role_permission');

        return DB::table($pivot)
            ->whereIn('role_id', $roleIds)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereNull('condition_id')
            ->pluck('permission_id')
            ->map(static fn ($id): string|int => $id)
            ->unique()
            ->values()
            ->all();
    }

    public function userHasActiveRoleNamed(
        Model $user,
        string $roleName,
        string|int $tenantId,
        string|int|null $teamId,
    ): bool {
        return ModelRole::query()
            ->where('tenant_id', $tenantId)
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey())
            ->whereNull('suspended_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) use ($teamId): void {
                if ($teamId === null) {
                    $query->whereNull('team_id');
                } else {
                    $query->where(function ($inner) use ($teamId): void {
                        $inner->whereNull('team_id')->orWhere('team_id', $teamId);
                    });
                }
            })
            ->whereHas('role', function ($query) use ($roleName, $tenantId): void {
                $query->where('name', $roleName)
                    ->where('activation_state', 'active')
                    ->where(function ($inner) use ($tenantId): void {
                        $inner->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
                    });
            })
            ->exists();
    }

    /**
     * @return class-string<Permission>
     */
    private function permissionModelClass(): string
    {
        $class = config('vaultrbac.models.permission');

        if (! is_string($class) || ! class_exists($class)) {
            throw new ConfigurationException('Invalid vaultrbac.models.permission configuration.');
        }

        return $class;
    }
}

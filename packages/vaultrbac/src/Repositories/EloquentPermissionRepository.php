<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\PermissionRepository;
use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Support\PrimaryKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

final class EloquentPermissionRepository implements PermissionRepository
{
    public function findById(mixed $id): ?Permission
    {
        $key = PrimaryKey::normalize($id);

        return Permission::query()->whereKey($key)->first();
    }

    public function getById(mixed $id): Permission
    {
        $permission = $this->findById($id);
        if ($permission === null) {
            throw new EntityNotFoundException(
                'Permission not found.',
                0,
                null,
                Permission::class,
                $id,
            );
        }

        return $permission;
    }

    public function existsById(mixed $id): bool
    {
        return $this->findById($id) !== null;
    }

    public function findByName(string $name, string|int|null $tenantId): ?Permission
    {
        $q = Permission::query()->where('name', $name);

        if ($tenantId === null) {
            $q->whereNull('tenant_id');
        } else {
            $q->where(function ($inner) use ($tenantId): void {
                $inner->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })->orderByRaw('CASE WHEN tenant_id = ? THEN 0 WHEN tenant_id IS NULL THEN 1 ELSE 2 END', [$tenantId]);
        }

        return $q->first();
    }

    public function getByName(string $name, string|int|null $tenantId): Permission
    {
        $permission = $this->findByName($name, $tenantId);
        if ($permission === null) {
            throw new EntityNotFoundException(
                'Permission not found.',
                0,
                null,
                Permission::class,
                $name,
            );
        }

        return $permission;
    }

    public function idsForAbilityNames(array $names, string|int $tenantId): array
    {
        if ($names === []) {
            return [];
        }

        return Permission::query()
            ->whereIn('name', $names)
            ->where(function ($q) use ($tenantId): void {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->pluck('id')
            ->map(static fn ($id): string|int => $id)
            ->values()
            ->all();
    }

    public function listForTenantCatalog(string|int $tenantId): Collection
    {
        return Permission::query()
            ->forTenantOrGlobal($tenantId)
            ->orderBy('name')
            ->get();
    }

    public function persist(Permission $permission): void
    {
        try {
            $permission->save();
        } catch (QueryException $e) {
            PersistenceExceptionMapper::mapQueryException($e, 'permission persist');
        }
    }
}

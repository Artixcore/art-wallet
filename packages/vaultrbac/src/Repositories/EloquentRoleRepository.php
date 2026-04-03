<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\RoleRepository;
use Artwallet\VaultRbac\Enums\RoleStatus;
use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Support\PrimaryKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

final class EloquentRoleRepository implements RoleRepository
{
    public function findById(mixed $id): ?Role
    {
        $key = PrimaryKey::normalize($id);

        return Role::query()->whereKey($key)->first();
    }

    public function getById(mixed $id): Role
    {
        $role = $this->findById($id);
        if ($role === null) {
            throw new EntityNotFoundException(
                'Role not found.',
                0,
                null,
                Role::class,
                $id,
            );
        }

        return $role;
    }

    public function existsById(mixed $id): bool
    {
        return $this->findById($id) !== null;
    }

    public function findByName(string $name, string|int|null $tenantId): ?Role
    {
        $q = Role::query()->where('name', $name);

        if ($tenantId === null) {
            $q->whereNull('tenant_id');
        } else {
            $q->where(function ($inner) use ($tenantId): void {
                $inner->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })->orderByRaw('CASE WHEN tenant_id = ? THEN 0 WHEN tenant_id IS NULL THEN 1 ELSE 2 END', [$tenantId]);
        }

        return $q->first();
    }

    public function getByName(string $name, string|int|null $tenantId): Role
    {
        $role = $this->findByName($name, $tenantId);
        if ($role === null) {
            throw new EntityNotFoundException(
                'Role not found.',
                0,
                null,
                Role::class,
                $name,
            );
        }

        return $role;
    }

    public function listActiveForTenantCatalog(string|int $tenantId): Collection
    {
        return Role::query()
            ->forTenantOrGlobal($tenantId)
            ->where('activation_state', RoleStatus::Active)
            ->orderBy('name')
            ->get();
    }

    public function persist(Role $role): void
    {
        try {
            $role->save();
        } catch (QueryException $e) {
            PersistenceExceptionMapper::mapQueryException($e, 'role persist');
        }
    }
}

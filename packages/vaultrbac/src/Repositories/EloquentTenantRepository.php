<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\TenantRepository;
use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Models\Tenant;
use Artwallet\VaultRbac\Support\PrimaryKey;
use Illuminate\Database\QueryException;

final class EloquentTenantRepository implements TenantRepository
{
    public function findById(mixed $id): ?Tenant
    {
        $key = PrimaryKey::normalize($id);

        return Tenant::query()->whereKey($key)->first();
    }

    public function getById(mixed $id): Tenant
    {
        $tenant = $this->findById($id);
        if ($tenant === null) {
            throw new EntityNotFoundException(
                'Tenant not found.',
                0,
                null,
                Tenant::class,
                $id,
            );
        }

        return $tenant;
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::query()->where('slug', $slug)->first();
    }

    public function getBySlug(string $slug): Tenant
    {
        $tenant = $this->findBySlug($slug);
        if ($tenant === null) {
            throw new EntityNotFoundException(
                'Tenant not found.',
                0,
                null,
                Tenant::class,
                $slug,
            );
        }

        return $tenant;
    }

    public function existsById(mixed $id): bool
    {
        return $this->findById($id) !== null;
    }

    public function persist(Tenant $tenant): void
    {
        try {
            $tenant->save();
        } catch (QueryException $e) {
            PersistenceExceptionMapper::mapQueryException($e, 'tenant persist');
        }
    }
}

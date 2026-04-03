<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Exceptions\Data\UnsupportedIdentifierTypeException;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Support\Collection;

interface RoleRepository
{
    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public function findById(mixed $id): ?Role;

    /**
     * @throws EntityNotFoundException
     * @throws UnsupportedIdentifierTypeException
     */
    public function getById(mixed $id): Role;

    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public function existsById(mixed $id): bool;

    /**
     * Resolve by unique (tenant_id, name). Use {@code $tenantId} null for global roles.
     */
    public function findByName(string $name, string|int|null $tenantId): ?Role;

    /**
     * @throws EntityNotFoundException
     */
    public function getByName(string $name, string|int|null $tenantId): Role;

    /**
     * Roles available in tenant catalog: global ({@code tenant_id} null) or tenant-owned, activation active.
     *
     * @return Collection<int, Role>
     */
    public function listActiveForTenantCatalog(string|int $tenantId): Collection;

    /**
     * Persist changes to an existing or new role model.
     *
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DuplicateEntityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DataIntegrityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\RepositoryException
     */
    public function persist(Role $role): void;
}

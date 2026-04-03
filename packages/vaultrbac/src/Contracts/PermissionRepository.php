<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Exceptions\Data\UnsupportedIdentifierTypeException;
use Artwallet\VaultRbac\Models\Permission;
use Illuminate\Support\Collection;

interface PermissionRepository
{
    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public function findById(mixed $id): ?Permission;

    /**
     * @throws EntityNotFoundException
     * @throws UnsupportedIdentifierTypeException
     */
    public function getById(mixed $id): Permission;

    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public function existsById(mixed $id): bool;

    /**
     * Global permissions have {@code tenant_id} null.
     */
    public function findByName(string $name, string|int|null $tenantId): ?Permission;

    /**
     * @throws EntityNotFoundException
     */
    public function getByName(string $name, string|int|null $tenantId): Permission;

    /**
     * @param  list<string>  $names
     * @return list<int|string>
     */
    public function idsForAbilityNames(array $names, string|int $tenantId): array;

    /**
     * @return Collection<int, Permission>
     */
    public function listForTenantCatalog(string|int $tenantId): Collection;

    /**
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DuplicateEntityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DataIntegrityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\RepositoryException
     */
    public function persist(Permission $permission): void;
}

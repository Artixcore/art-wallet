<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Exceptions\Data\UnsupportedIdentifierTypeException;
use Artwallet\VaultRbac\Models\Tenant;

interface TenantRepository
{
    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public function findById(mixed $id): ?Tenant;

    /**
     * @throws EntityNotFoundException
     * @throws UnsupportedIdentifierTypeException
     */
    public function getById(mixed $id): Tenant;

    public function findBySlug(string $slug): ?Tenant;

    /**
     * @throws EntityNotFoundException
     */
    public function getBySlug(string $slug): Tenant;

    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public function existsById(mixed $id): bool;

    /**
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DuplicateEntityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DataIntegrityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\RepositoryException
     */
    public function persist(Tenant $tenant): void;
}

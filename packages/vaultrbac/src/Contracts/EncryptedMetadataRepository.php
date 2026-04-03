<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Exceptions\Data\UnsupportedIdentifierTypeException;
use Artwallet\VaultRbac\Models\EncryptedMetadata;

interface EncryptedMetadataRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DuplicateEntityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DataIntegrityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\RepositoryException
     */
    public function create(array $attributes): EncryptedMetadata;

    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public function findById(mixed $id): ?EncryptedMetadata;

    /**
     * @throws EntityNotFoundException
     * @throws UnsupportedIdentifierTypeException
     */
    public function getById(mixed $id): EncryptedMetadata;

    /**
     * @throws EntityNotFoundException
     * @throws UnsupportedIdentifierTypeException
     */
    public function deleteById(mixed $id): void;
}

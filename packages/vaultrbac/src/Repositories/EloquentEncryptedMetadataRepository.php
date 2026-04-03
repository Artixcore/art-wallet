<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\EncryptedMetadataRepository;
use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Models\EncryptedMetadata;
use Artwallet\VaultRbac\Support\PrimaryKey;
use Illuminate\Database\QueryException;

final class EloquentEncryptedMetadataRepository implements EncryptedMetadataRepository
{
    public function create(array $attributes): EncryptedMetadata
    {
        try {
            /** @var EncryptedMetadata $row */
            $row = EncryptedMetadata::query()->create($attributes);

            return $row;
        } catch (QueryException $e) {
            PersistenceExceptionMapper::mapQueryException($e, 'encrypted metadata create');
        }
    }

    public function findById(mixed $id): ?EncryptedMetadata
    {
        $key = PrimaryKey::normalize($id);

        return EncryptedMetadata::query()->whereKey($key)->first();
    }

    public function getById(mixed $id): EncryptedMetadata
    {
        $row = $this->findById($id);
        if ($row === null) {
            throw new EntityNotFoundException(
                'Encrypted metadata record not found.',
                0,
                null,
                EncryptedMetadata::class,
                $id,
            );
        }

        return $row;
    }

    public function deleteById(mixed $id): void
    {
        $row = $this->getById($id);
        try {
            $row->delete();
        } catch (QueryException $e) {
            PersistenceExceptionMapper::mapQueryException($e, 'encrypted metadata delete');
        }
    }
}

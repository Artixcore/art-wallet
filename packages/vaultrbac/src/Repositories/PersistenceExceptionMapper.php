<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Exceptions\Data\DataIntegrityException;
use Artwallet\VaultRbac\Exceptions\Data\DuplicateEntityException;
use Artwallet\VaultRbac\Exceptions\Data\RepositoryException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Maps low-level SQL errors to package data-layer exceptions.
 */
final class PersistenceExceptionMapper
{
    /**
     * @return never
     */
    public static function mapQueryException(QueryException $e, string $operationContext = 'database'): void
    {
        if ($e instanceof UniqueConstraintViolationException) {
            throw new DuplicateEntityException(
                'A record with this key already exists.',
                0,
                $e,
            );
        }

        if (self::isForeignKeyOrIntegrity($e)) {
            throw new DataIntegrityException(
                'The operation would break data integrity constraints.',
                0,
                $e,
            );
        }

        throw new RepositoryException(
            sprintf('A persistence error occurred during %s.', $operationContext),
            0,
            $e,
        );
    }

    private static function isForeignKeyOrIntegrity(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'foreign key')
            || str_contains($message, 'integrity constraint')
            || str_contains($message, 'violates foreign key');
    }
}

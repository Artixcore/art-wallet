<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Exceptions\Data\UnsupportedIdentifierTypeException;
use Artwallet\VaultRbac\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepository
{
    /**
     * Insert a new audit row. Does not compute chain hashes; prefer {@see \Artwallet\VaultRbac\Audit\DatabaseAuditSink} for signed chains.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DataIntegrityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\RepositoryException
     */
    public function append(array $attributes): AuditLog;

    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public function findById(mixed $id): ?AuditLog;

    /**
     * @throws EntityNotFoundException
     * @throws UnsupportedIdentifierTypeException
     */
    public function getById(mixed $id): AuditLog;

    /**
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function paginateRecentForTenant(string|int $tenantId, int $perPage = 50): LengthAwarePaginator;
}

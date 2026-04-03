<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\AuditLogRepository;
use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Models\AuditLog;
use Artwallet\VaultRbac\Support\PrimaryKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;

final class EloquentAuditLogRepository implements AuditLogRepository
{
    public function append(array $attributes): AuditLog
    {
        try {
            /** @var AuditLog $log */
            $log = AuditLog::query()->create($attributes);

            return $log;
        } catch (QueryException $e) {
            PersistenceExceptionMapper::mapQueryException($e, 'audit log append');
        }
    }

    public function findById(mixed $id): ?AuditLog
    {
        $key = PrimaryKey::normalize($id);

        return AuditLog::query()->whereKey($key)->first();
    }

    public function getById(mixed $id): AuditLog
    {
        $log = $this->findById($id);
        if ($log === null) {
            throw new EntityNotFoundException(
                'Audit log not found.',
                0,
                null,
                AuditLog::class,
                $id,
            );
        }

        return $log;
    }

    public function paginateRecentForTenant(string|int $tenantId, int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}

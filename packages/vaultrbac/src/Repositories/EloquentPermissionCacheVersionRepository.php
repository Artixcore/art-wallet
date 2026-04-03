<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Models\PermissionCacheVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentPermissionCacheVersionRepository implements PermissionCacheVersionRepository
{
    public function getVersion(
        string|int $tenantId,
        string $scope,
        string $subjectType = '',
        int $subjectId = 0,
    ): int {
        $row = PermissionCacheVersion::query()
            ->where('tenant_id', $tenantId)
            ->where('scope', $scope)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->first();

        return $row !== null ? (int) $row->version : 0;
    }

    public function bump(
        string|int $tenantId,
        string $scope,
        string $subjectType = '',
        int $subjectId = 0,
    ): int {
        try {
            return (int) DB::transaction(function () use ($tenantId, $scope, $subjectType, $subjectId): int {
                $row = PermissionCacheVersion::query()
                    ->where('tenant_id', $tenantId)
                    ->where('scope', $scope)
                    ->where('subject_type', $subjectType)
                    ->where('subject_id', $subjectId)
                    ->lockForUpdate()
                    ->first();

                if ($row === null) {
                    PermissionCacheVersion::query()->create([
                        'tenant_id' => $tenantId,
                        'scope' => $scope,
                        'subject_type' => $subjectType,
                        'subject_id' => $subjectId,
                        'version' => 1,
                    ]);

                    return 1;
                }

                $row->increment('version');

                return (int) $row->fresh()->version;
            });
        } catch (QueryException $e) {
            PersistenceExceptionMapper::mapQueryException($e, 'permission cache version bump');
        }
    }
}

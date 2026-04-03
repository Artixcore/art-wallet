<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

interface PermissionCacheVersionRepository
{
    /**
     * Returns {@code 0} when no row exists (cold cache).
     */
    public function getVersion(
        string|int $tenantId,
        string $scope,
        string $subjectType = '',
        int $subjectId = 0,
    ): int;

    /**
     * Atomically increments the version for the tuple, creating the row at version 1 if missing.
     *
     * @throws \Artwallet\VaultRbac\Exceptions\Data\RepositoryException
     */
    public function bump(
        string|int $tenantId,
        string $scope,
        string $subjectType = '',
        int $subjectId = 0,
    ): int;
}

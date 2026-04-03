<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

/**
 * Bumps capability / catalog versions for safe cache invalidation without
 * scanning keyspaces (see package blueprint §11).
 */
interface CacheInvalidator
{
    public function bumpAssignmentVersion(string|int|null $tenantId, string|int $userId): void;

    public function bumpCatalogVersion(): void;
}

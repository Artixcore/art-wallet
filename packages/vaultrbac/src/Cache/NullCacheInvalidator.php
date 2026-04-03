<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Cache;

use Artwallet\VaultRbac\Contracts\CacheInvalidator;

final class NullCacheInvalidator implements CacheInvalidator
{
    public function bumpAssignmentVersion(string|int|null $tenantId, string|int $userId): void
    {
        // No-op until cache versioning is wired (Phase 3+).
    }

    public function bumpCatalogVersion(): void
    {
        // No-op until catalog cache is wired.
    }
}

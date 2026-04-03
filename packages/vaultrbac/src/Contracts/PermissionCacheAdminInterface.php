<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Api\Dto\CacheFlushResult;
use Artwallet\VaultRbac\Api\Dto\CacheWarmTarget;
use Artwallet\VaultRbac\Api\Dto\CacheWarmupResult;

/**
 * Operational cache/version management for permission freshness and optional Laravel cache snapshots.
 */
interface PermissionCacheAdminInterface
{
    /**
     * Preload version snapshots into the application cache (best-effort).
     */
    public function warm(CacheWarmTarget $target): CacheWarmupResult;

    /**
     * Invalidate local snapshots and/or bump permission version rows (fail-closed safe).
     */
    public function flush(CacheWarmTarget $target, bool $bumpVersions): CacheFlushResult;
}

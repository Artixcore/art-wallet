<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Cache;

use Artwallet\VaultRbac\Contracts\CacheInvalidator;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Bumps rows in {@see PermissionCacheVersionRepository} so version-stamped caches
 * invalidate without scanning Redis keyspaces.
 */
final class StoreBackedCacheInvalidator implements CacheInvalidator
{
    public function __construct(
        private readonly PermissionCacheVersionRepository $versions,
        private readonly ConfigRepository $config,
    ) {}

    public function bumpAssignmentVersion(string|int|null $tenantId, string|int $userId): void
    {
        if ($tenantId === null) {
            return;
        }

        $subjectType = (string) $this->config->get('vaultrbac.cache_admin.assignment_subject_type', 'assignment');
        $this->versions->bump($tenantId, $subjectType, '', (int) $userId);
    }

    public function bumpCatalogVersion(): void
    {
        if (! (bool) $this->config->get('vaultrbac.cache_invalidator.bump_all_tenants_on_catalog', false)) {
            return;
        }

        $scope = (string) $this->config->get('vaultrbac.freshness.scope', 'tenant');

        foreach (Tenant::query()->cursor() as $tenant) {
            $this->versions->bump($tenant->getKey(), $scope);
        }
    }
}

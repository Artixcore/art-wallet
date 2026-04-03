<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Services;

use Artwallet\VaultRbac\Api\Dto\CacheFlushResult;
use Artwallet\VaultRbac\Api\Dto\CacheWarmTarget;
use Artwallet\VaultRbac\Api\Dto\CacheWarmupResult;
use Artwallet\VaultRbac\Contracts\CacheInvalidator;
use Artwallet\VaultRbac\Contracts\PermissionCacheAdminInterface;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Models\ModelRole;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Throwable;

final class PermissionCacheAdminService implements PermissionCacheAdminInterface
{
    public function __construct(
        private readonly PermissionCacheVersionRepository $versions,
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
        private readonly CacheInvalidator $cacheInvalidator,
    ) {}

    public function warm(CacheWarmTarget $target): CacheWarmupResult
    {
        try {
            $count = 0;
            $ttl = (int) $this->config->get('vaultrbac.cache_admin.warm_ttl_seconds', 3600);
            $scope = (string) $this->config->get('vaultrbac.freshness.scope', 'tenant');

            if ($target->allTenants) {
                foreach (Tenant::query()->cursor() as $tenant) {
                    $tid = $tenant->getKey();
                    $version = $this->versions->getVersion($tid, $scope);
                    $this->cache->put($this->tenantWarmKey((string) $tid, $scope), $version, $ttl);
                    $count++;
                }

                return new CacheWarmupResult(true, $count);
            }

            if ($target->tenantId === null) {
                return new CacheWarmupResult(false, 0, 'tenantId is required unless --all-tenants.');
            }

            $tenantId = $target->tenantId;
            $version = $this->versions->getVersion($tenantId, $scope);
            $this->cache->put($this->tenantWarmKey((string) $tenantId, $scope), $version, $ttl);
            $count++;

            if ($target->userId !== null) {
                $subjectType = (string) $this->config->get('vaultrbac.cache_admin.assignment_subject_type', 'assignment');
                $uid = (int) $target->userId;
                $userVersion = $this->versions->getVersion($tenantId, $subjectType, '', $uid);
                $this->cache->put($this->userWarmKey((string) $tenantId, $uid), $userVersion, $ttl);
                $count++;
            } else {
                $distinct = ModelRole::query()
                    ->where('tenant_id', $tenantId)
                    ->distinct()
                    ->pluck('model_id');

                foreach ($distinct as $modelId) {
                    $subjectType = (string) $this->config->get('vaultrbac.cache_admin.assignment_subject_type', 'assignment');
                    $userVersion = $this->versions->getVersion($tenantId, $subjectType, '', (int) $modelId);
                    $this->cache->put($this->userWarmKey((string) $tenantId, (int) $modelId), $userVersion, $ttl);
                    $count++;
                }
            }

            return new CacheWarmupResult(true, $count);
        } catch (Throwable $e) {
            if ($this->config->get('vaultrbac.cache_admin.throw_on_error', false)) {
                throw $e;
            }

            return new CacheWarmupResult(false, 0, $e->getMessage());
        }
    }

    public function flush(CacheWarmTarget $target, bool $bumpVersions): CacheFlushResult
    {
        try {
            $count = 0;
            $scope = (string) $this->config->get('vaultrbac.freshness.scope', 'tenant');

            if ($target->allTenants) {
                foreach (Tenant::query()->cursor() as $tenant) {
                    $tid = $tenant->getKey();
                    $count += $this->flushTenantCaches((string) $tid, $scope, $bumpVersions);
                }

                return new CacheFlushResult(true, $count);
            }

            if ($target->tenantId === null) {
                return new CacheFlushResult(false, 0, 'tenantId is required unless --all-tenants.');
            }

            $tenantId = (string) $target->tenantId;
            $count += $this->flushTenantCaches($tenantId, $scope, $bumpVersions);

            if ($target->userId !== null) {
                $this->cache->forget($this->userWarmKey($tenantId, (int) $target->userId));
                $count++;
                if ($bumpVersions) {
                    $subjectType = (string) $this->config->get('vaultrbac.cache_admin.assignment_subject_type', 'assignment');
                    $this->versions->bump($target->tenantId, $subjectType, '', (int) $target->userId);
                    $count++;
                }
            } else {
                foreach ($this->distinctSubjectIdsInTenant($target->tenantId) as $modelId) {
                    $this->cache->forget($this->userWarmKey($tenantId, (int) $modelId));
                    $count++;
                }
            }

            return new CacheFlushResult(true, $count);
        } catch (Throwable $e) {
            if ($this->config->get('vaultrbac.cache_admin.throw_on_error', false)) {
                throw $e;
            }

            return new CacheFlushResult(false, 0, $e->getMessage());
        }
    }

    private function flushTenantCaches(string $tenantId, string $scope, bool $bumpVersions): int
    {
        $count = 0;
        $this->cache->forget($this->tenantWarmKey($tenantId, $scope));
        $count++;

        if ($bumpVersions) {
            $this->versions->bump($tenantId, $scope);
            $count++;
            $this->cacheInvalidator->bumpCatalogVersion();
            $count++;
        }

        return $count;
    }

    private function tenantWarmKey(string $tenantId, string $scope): string
    {
        $prefix = (string) $this->config->get('vaultrbac.cache.prefix', 'vaultrbac');

        return $prefix.':warm:tenant:'.$tenantId.':'.$scope;
    }

    private function userWarmKey(string $tenantId, int $userId): string
    {
        $prefix = (string) $this->config->get('vaultrbac.cache.prefix', 'vaultrbac');

        return $prefix.':warm:user:'.$tenantId.':'.$userId;
    }

    /**
     * @return list<int>
     */
    private function distinctSubjectIdsInTenant(string|int $tenantId): array
    {
        $roleIds = ModelRole::query()
            ->where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('model_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return array_values(array_unique($roleIds));
    }
}

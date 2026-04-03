<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Resolvers;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Contracts\SuperUserGuard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Caches boolean authorize() outcomes stamped with tenant + per-user assignment versions.
 * Super-user bypass outcomes are never read from or written to cache (fail-safe).
 */
final class VersionedCachingPermissionResolver implements PermissionResolverInterface
{
    public function __construct(
        private readonly PermissionResolverInterface $inner,
        private readonly CacheRepository $cache,
        private readonly PermissionCacheVersionRepository $versions,
        private readonly ConfigRepository $config,
        private readonly SuperUserGuard $superUserGuard,
        private readonly LogManager $logManager,
    ) {}

    public function authorize(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        if (! (bool) $this->config->get('vaultrbac.cache.decisions_enabled', false)) {
            return $this->inner->authorize($context, $ability, $resource);
        }

        $tenantId = $context->tenantId;
        if ($tenantId === null) {
            return $this->inner->authorize($context, $ability, $resource);
        }

        if ($this->superUserGuard->allowsPrivilegedBypass($context)) {
            return $this->inner->authorize($context, $ability, $resource);
        }

        $scope = (string) $this->config->get('vaultrbac.freshness.scope', 'tenant');
        $subjectType = (string) $this->config->get('vaultrbac.cache_admin.assignment_subject_type', 'assignment');
        $strictVersion = (bool) $this->config->get('vaultrbac.freshness.strict_version_read', false);
        $failClosedCache = (bool) $this->config->get('vaultrbac.cache.fail_closed_on_cache_error', false);

        try {
            $catalogV = $this->versions->getVersion($tenantId, $scope);
            $uid = $this->resolveSubjectNumericId($context);
            $assignV = $uid > 0
                ? $this->versions->getVersion($tenantId, $subjectType, '', $uid)
                : 0;
        } catch (Throwable $e) {
            $this->logCacheEvent('version_read_failed', $context, $ability, ['exception' => $e::class]);

            return $strictVersion
                ? false
                : $this->inner->authorize($context, $ability, $resource);
        }

        $name = trim((string) $ability);
        $key = $this->decisionKey($tenantId, $catalogV, $assignV, $context, $name, $resource);

        try {
            $payload = $this->cache->get($key);
            if (is_array($payload)
                && ($payload['cv'] ?? null) === $catalogV
                && ($payload['av'] ?? null) === $assignV
                && \array_key_exists('g', $payload)) {
                $this->logCacheEvent('decision_hit', $context, $ability, ['catalog_v' => $catalogV, 'assign_v' => $assignV]);

                return (bool) $payload['g'];
            }
        } catch (Throwable $e) {
            $this->logCacheEvent('cache_get_error', $context, $ability, ['exception' => $e::class]);
            if ($failClosedCache) {
                return false;
            }
        }

        $this->logCacheEvent('decision_miss', $context, $ability, ['catalog_v' => $catalogV, 'assign_v' => $assignV]);

        $granted = $this->inner->authorize($context, $ability, $resource);

        try {
            $ttl = (int) $this->config->get('vaultrbac.cache.decision_ttl_seconds', 60);
            $this->cache->put($key, ['g' => $granted, 'cv' => $catalogV, 'av' => $assignV], max(1, $ttl));
        } catch (Throwable $e) {
            $this->logCacheEvent('cache_put_error', $context, $ability, ['exception' => $e::class]);
        }

        return $granted;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function logCacheEvent(
        string $event,
        AuthorizationContext $context,
        string|\Stringable $ability,
        array $extra,
    ): void {
        if (! (bool) $this->config->get('vaultrbac.observability.log_cache_resolution', false)) {
            return;
        }

        $channel = $this->config->get('vaultrbac.integration.log_channel');
        /** @var LoggerInterface $logger */
        $logger = is_string($channel) && $channel !== ''
            ? $this->logManager->channel($channel)
            : $this->logManager->channel();

        $name = trim((string) $ability);
        $logger->info('VaultRBAC cache resolution', array_merge([
            'vaultrbac.event' => $event,
            'tenant_id' => $context->tenantId,
            'ability_hash' => hash('sha256', $name),
        ], $extra));
    }

    private function resolveSubjectNumericId(AuthorizationContext $context): int
    {
        $user = $context->user;
        if ($user === null) {
            return 0;
        }
        if ($user instanceof Model) {
            $key = $user->getKey();

            return is_numeric($key) ? (int) $key : 0;
        }

        $id = $user->getAuthIdentifier();

        return is_numeric($id) ? (int) $id : 0;
    }

    private function decisionKey(
        string|int $tenantId,
        int $catalogV,
        int $assignV,
        AuthorizationContext $context,
        string $ability,
        ?object $resource,
    ): string {
        $prefix = (string) $this->config->get('vaultrbac.cache.prefix', 'vaultrbac');
        $team = $context->teamId !== null ? (string) $context->teamId : '0';
        $morph = 'guest';
        if ($context->user instanceof Model) {
            $morph = $context->user->getMorphClass();
        }
        $uid = $this->resolveSubjectNumericId($context);
        $res = '0';
        if ($resource !== null) {
            if ($resource instanceof Model) {
                $res = $resource->getMorphClass().':'.$resource->getKey();
            } else {
                $res = 'oid:'.spl_object_id($resource);
            }
        }
        $hash = hash('sha256', $ability);

        return "{$prefix}:dec:v1:{$tenantId}:{$catalogV}:{$assignV}:{$team}:{$morph}:{$uid}:{$res}:{$hash}";
    }
}

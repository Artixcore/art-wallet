<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api;

use Artwallet\VaultRbac\Api\Dto\PermissionDecision;
use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Immutable fluent authorization checks; each step returns a new instance.
 */
final class AuthorizationQuery
{
    private function __construct(
        private readonly PermissionResolverInterface $resolver,
        private readonly AuthorizationContextFactory $contextFactory,
        private readonly ConfigRepository $config,
        private readonly ?PermissionCacheVersionRepository $versions,
        private readonly ?Authenticatable $user,
        private readonly string|int|null $tenantId,
        private readonly string|int|null $teamId,
        private readonly ?object $resource,
        private readonly bool $strictTenant,
    ) {}

    public static function make(
        PermissionResolverInterface $resolver,
        AuthorizationContextFactory $contextFactory,
        ConfigRepository $config,
        ?PermissionCacheVersionRepository $versions = null,
    ): self {
        return new self($resolver, $contextFactory, $config, $versions, null, null, null, null, false);
    }

    public function forUser(?Authenticatable $user): self
    {
        return new self(
            $this->resolver,
            $this->contextFactory,
            $this->config,
            $this->versions,
            $user,
            $this->tenantId,
            $this->teamId,
            $this->resource,
            $this->strictTenant,
        );
    }

    public function inTenant(string|int|null $tenantId): self
    {
        return new self(
            $this->resolver,
            $this->contextFactory,
            $this->config,
            $this->versions,
            $this->user,
            $tenantId,
            $this->teamId,
            $this->resource,
            $this->strictTenant,
        );
    }

    public function inTeam(string|int|null $teamId): self
    {
        return new self(
            $this->resolver,
            $this->contextFactory,
            $this->config,
            $this->versions,
            $this->user,
            $this->tenantId,
            $teamId,
            $this->resource,
            $this->strictTenant,
        );
    }

    public function forResource(?object $resource): self
    {
        return new self(
            $this->resolver,
            $this->contextFactory,
            $this->config,
            $this->versions,
            $this->user,
            $this->tenantId,
            $this->teamId,
            $resource,
            $this->strictTenant,
        );
    }

    public function withStrictTenantRequirement(): self
    {
        return new self(
            $this->resolver,
            $this->contextFactory,
            $this->config,
            $this->versions,
            $this->user,
            $this->tenantId,
            $this->teamId,
            $this->resource,
            true,
        );
    }

    public function decide(string|\Stringable $ability): PermissionDecision
    {
        $name = trim((string) $ability);
        if ($name === '') {
            return PermissionDecision::deny('', $this->effectiveTenantId(), PermissionDenialReason::EmptyAbility);
        }

        if (! $this->user instanceof Model) {
            return PermissionDecision::deny($name, $this->effectiveTenantId(), PermissionDenialReason::GuestUser);
        }

        $tenant = $this->effectiveTenantId();
        if ($tenant === null && ($this->strictTenant || (bool) $this->config->get('vaultrbac.require_tenant_context', true))) {
            return PermissionDecision::deny($name, null, PermissionDenialReason::StrictTenantRequired);
        }

        $cacheVersion = null;
        $versionResolved = true;
        if ($this->versions !== null && $tenant !== null) {
            $scope = (string) $this->config->get('vaultrbac.freshness.scope', 'tenant');
            $strictVersion = (bool) $this->config->get('vaultrbac.freshness.strict_version_read', false);
            try {
                $cacheVersion = $this->versions->getVersion($tenant, $scope);
            } catch (\Throwable) {
                $versionResolved = false;
                if ($strictVersion) {
                    return PermissionDecision::deny(
                        $name,
                        $tenant,
                        PermissionDenialReason::VersionReadFailed,
                        null,
                        false,
                    );
                }
            }
        }

        $context = $this->buildContext();
        $granted = $this->resolver->authorize($context, $name, $this->resource);

        if ($granted) {
            return PermissionDecision::allow($name, $tenant, $cacheVersion, $versionResolved);
        }

        return PermissionDecision::deny(
            $name,
            $tenant,
            PermissionDenialReason::ResolverDenied,
            $cacheVersion,
            $versionResolved,
        );
    }

    public function can(string|\Stringable $ability): bool
    {
        return $this->decide($ability)->toBool();
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function canAny(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if ($this->can($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function canAll(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if (! $this->can($ability)) {
                return false;
            }
        }

        return true;
    }

    private function effectiveTenantId(): string|int|null
    {
        if ($this->tenantId !== null) {
            return $this->tenantId;
        }

        return $this->config->get('vaultrbac.default_tenant_id');
    }

    private function buildContext(): AuthorizationContext
    {
        $base = $this->contextFactory->makeFor($this->user);
        $tenant = $this->tenantId ?? $base->tenantId ?? $this->config->get('vaultrbac.default_tenant_id');
        $ctx = $base->withTenant($tenant);

        return $this->teamId !== null ? $ctx->withTeam($this->teamId) : $ctx;
    }
}

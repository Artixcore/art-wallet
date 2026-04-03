<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Concerns;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Support\RequestAuthorization;
use Artwallet\VaultRbac\VaultRbac as VaultRbacManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

/**
 * For controllers, form requests, or policies that delegate to {@see VaultRbacManager}.
 */
trait AuthorizesWithVault
{
    /**
     * @throws AuthorizationException
     */
    protected function authorizeVault(string|\Stringable $ability, ?object $resource = null): void
    {
        if (! $this->vault()->check($ability, $resource)) {
            throw new AuthorizationException;
        }
    }

    protected function vaultCanFor(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return $this->vault()->checkFor($context, $ability, $resource);
    }

    /**
     * @throws AuthorizationException
     */
    protected function authorizeVaultFor(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): void {
        if (! $this->vaultCanFor($context, $ability, $resource)) {
            throw new AuthorizationException;
        }
    }

    /**
     * Build a context from the current request user plus optional tenant/team overrides.
     */
    protected function vaultPermissionContextFromRequest(
        Request $request,
        string|int|null $tenantId = null,
        string|int|null $teamId = null,
    ): AuthorizationContext {
        return app(RequestAuthorization::class)->permissionContextFromRequest($request, $tenantId, $teamId);
    }

    protected function vault(): VaultRbacManager
    {
        return app(VaultRbacManager::class);
    }
}

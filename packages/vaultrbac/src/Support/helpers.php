<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Support\RequestAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

if (! function_exists('vaultrbac_current_tenant')) {
    function vaultrbac_current_tenant(): string|int|null
    {
        return app(RequestAuthorization::class)->currentTenant();
    }
}

if (! function_exists('vaultrbac_current_authorization_context')) {
    function vaultrbac_current_authorization_context(): AuthorizationContext
    {
        return app(RequestAuthorization::class)->currentAuthorizationContext();
    }
}

if (! function_exists('vaultrbac_permission_context_from_request')) {
    function vaultrbac_permission_context_from_request(
        Request $request,
        string|int|null $tenantId = null,
        string|int|null $teamId = null,
    ): AuthorizationContext {
        return app(RequestAuthorization::class)->permissionContextFromRequest($request, $tenantId, $teamId);
    }
}

if (! function_exists('vaultrbac_safe_authenticated_user')) {
    function vaultrbac_safe_authenticated_user(Request $request): ?Model
    {
        return app(RequestAuthorization::class)->safeAuthenticatedUser($request);
    }
}

if (! function_exists('vaultrbac_resolve_tenant_from_route')) {
    function vaultrbac_resolve_tenant_from_route(Request $request, string $parameter): string|int|null
    {
        return app(RequestAuthorization::class)->resolveTenantFromRoute($request, $parameter);
    }
}

if (! function_exists('vaultrbac_resolve_context_from_route')) {
    /**
     * @param  array{tenant?:string,team?:string}  $routeParameterNames
     * @return array{0: string|int|null, 1: string|int|null}
     */
    function vaultrbac_resolve_context_from_route(Request $request, array $routeParameterNames): array
    {
        return app(RequestAuthorization::class)->resolveContextFromRoute($request, $routeParameterNames);
    }
}

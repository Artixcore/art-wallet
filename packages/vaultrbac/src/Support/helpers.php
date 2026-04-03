<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Support\RequestAuthorization;
use Artwallet\VaultRbac\VaultRbac;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

if (! function_exists('vault_rbac')) {
    /**
     * Root VaultRbac application service (prefer injecting {@see VaultRbac} in domain code).
     */
    function vault_rbac(): VaultRbac
    {
        return app(VaultRbac::class);
    }
}

if (! function_exists('vault_rbac_can')) {
    function vault_rbac_can(string|\Stringable $ability, ?object $resource = null): bool
    {
        return vault_rbac()->can($ability, $resource);
    }
}

if (! function_exists('vault_rbac_can_any')) {
    /**
     * @param  list<string|\Stringable>  $abilities
     */
    function vault_rbac_can_any(array $abilities, ?object $resource = null): bool
    {
        return vault_rbac()->canAny($abilities, $resource);
    }
}

if (! function_exists('vault_rbac_can_all')) {
    /**
     * @param  list<string|\Stringable>  $abilities
     */
    function vault_rbac_can_all(array $abilities, ?object $resource = null): bool
    {
        return vault_rbac()->canAll($abilities, $resource);
    }
}

if (! function_exists('vault_rbac_can_in_tenant')) {
    function vault_rbac_can_in_tenant(
        ?\Illuminate\Contracts\Auth\Authenticatable $user,
        string|int $tenantId,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return vault_rbac()->canInTenant($user, $tenantId, $ability, $resource);
    }
}

if (! function_exists('vault_rbac_can_in_context')) {
    function vault_rbac_can_in_context(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return vault_rbac()->canInContext($context, $ability, $resource);
    }
}

if (! function_exists('vault_rbac_assign_role')) {
    function vault_rbac_assign_role(
        Model $model,
        \Artwallet\VaultRbac\Models\Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): \Artwallet\VaultRbac\Api\Dto\AssignmentResult {
        return vault_rbac()->assignRole($model, $role, $tenantId, $teamId, $assignedBy);
    }
}

if (! function_exists('vault_rbac_give_permission')) {
    function vault_rbac_give_permission(
        Model $model,
        \Artwallet\VaultRbac\Models\Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        string $effect = 'allow',
        string|int|null $assignedBy = null,
    ): \Artwallet\VaultRbac\Api\Dto\AssignmentResult {
        return vault_rbac()->givePermission($model, $permission, $tenantId, $teamId, $effect, $assignedBy);
    }
}

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

<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('rbac')) {
    function rbac(): \Artwallet\VaultRbac\VaultRbac
    {
        return vault_rbac();
    }
}

if (! function_exists('rbac_can')) {
    function rbac_can(string|\Stringable $ability, ?object $resource = null): bool
    {
        return vault_rbac_can($ability, $resource);
    }
}

if (! function_exists('rbac_can_any')) {
    /**
     * @param  list<string|\Stringable>  $abilities
     */
    function rbac_can_any(array $abilities, ?object $resource = null): bool
    {
        return vault_rbac_can_any($abilities, $resource);
    }
}

if (! function_exists('rbac_can_all')) {
    /**
     * @param  list<string|\Stringable>  $abilities
     */
    function rbac_can_all(array $abilities, ?object $resource = null): bool
    {
        return vault_rbac_can_all($abilities, $resource);
    }
}

if (! function_exists('rbac_can_in_tenant')) {
    function rbac_can_in_tenant(
        ?Authenticatable $user,
        string|int $tenantId,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return vault_rbac_can_in_tenant($user, $tenantId, $ability, $resource);
    }
}

if (! function_exists('rbac_can_in_context')) {
    function rbac_can_in_context(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return vault_rbac_can_in_context($context, $ability, $resource);
    }
}

if (! function_exists('rbac_assign_role')) {
    function rbac_assign_role(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): \Artwallet\VaultRbac\Api\Dto\AssignmentResult {
        return vault_rbac_assign_role($model, $role, $tenantId, $teamId, $assignedBy);
    }
}

if (! function_exists('rbac_give_permission')) {
    function rbac_give_permission(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        string $effect = 'allow',
        string|int|null $assignedBy = null,
    ): \Artwallet\VaultRbac\Api\Dto\AssignmentResult {
        return vault_rbac_give_permission($model, $permission, $tenantId, $teamId, $effect, $assignedBy);
    }
}

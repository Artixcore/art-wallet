<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool check(string|\Stringable $ability, ?object $resource = null)
 * @method static bool checkFor(\Artwallet\VaultRbac\Context\AuthorizationContext $context, string|\Stringable $ability, ?object $resource = null)
 * @method static void assignRole(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Role|string|int $role, string|int $tenantId, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static void revokeRole(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Role|string|int $role, string|int $tenantId, string|int|null $teamId = null)
 * @method static void syncRoles(\Illuminate\Database\Eloquent\Model $model, array $roles, string|int $tenantId, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static void givePermissionTo(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, string $effect = 'allow', string|int|null $assignedBy = null)
 * @method static void revokePermissionTo(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, ?string $effect = null)
 *
 * @see \Artwallet\VaultRbac\VaultRbac
 */
class VaultRbac extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Artwallet\VaultRbac\VaultRbac::class;
    }
}

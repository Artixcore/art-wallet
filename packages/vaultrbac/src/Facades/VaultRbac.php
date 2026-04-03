<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Artwallet\VaultRbac\Api\AuthorizationQuery query()
 * @method static \Artwallet\VaultRbac\Api\AuthorizationQuery forUser(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool check(string|\Stringable $ability, ?object $resource = null)
 * @method static bool can(string|\Stringable $ability, ?object $resource = null)
 * @method static bool checkFor(\Artwallet\VaultRbac\Context\AuthorizationContext $context, string|\Stringable $ability, ?object $resource = null)
 * @method static bool canFor(\Artwallet\VaultRbac\Context\AuthorizationContext $context, string|\Stringable $ability, ?object $resource = null)
 * @method static bool checkAny(array $abilities, ?object $resource = null)
 * @method static bool canAny(array $abilities, ?object $resource = null)
 * @method static bool checkAll(array $abilities, ?object $resource = null)
 * @method static bool canAll(array $abilities, ?object $resource = null)
 * @method static bool checkAnyFor(\Artwallet\VaultRbac\Context\AuthorizationContext $context, array $abilities, ?object $resource = null)
 * @method static bool canAnyFor(\Artwallet\VaultRbac\Context\AuthorizationContext $context, array $abilities, ?object $resource = null)
 * @method static bool checkAllFor(\Artwallet\VaultRbac\Context\AuthorizationContext $context, array $abilities, ?object $resource = null)
 * @method static bool canAllFor(\Artwallet\VaultRbac\Context\AuthorizationContext $context, array $abilities, ?object $resource = null)
 * @method static bool canInTenant(?\Illuminate\Contracts\Auth\Authenticatable $user, string|int $tenantId, string|\Stringable $ability, ?object $resource = null)
 * @method static bool canInContext(\Artwallet\VaultRbac\Context\AuthorizationContext $context, string|\Stringable $ability, ?object $resource = null)
 * @method static bool hasRole(string|\Stringable $roleName, string|int|null $tenantId = null, string|int|null $teamId = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult assignRole(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Role|string|int $role, string|int $tenantId, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult revokeRole(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Role|string|int $role, string|int $tenantId, string|int|null $teamId = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult syncRoles(\Illuminate\Database\Eloquent\Model $model, array $roles, string|int $tenantId, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult givePermission(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, string $effect = 'allow', string|int|null $assignedBy = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult revokePermission(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, ?string $effect = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult syncPermissions(\Illuminate\Database\Eloquent\Model $model, array $permissions, string|int $tenantId, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult givePermissionTo(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, string $effect = 'allow', string|int|null $assignedBy = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult revokePermissionTo(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, ?string $effect = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult temporaryRole(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Role|string|int $role, string|int $tenantId, \Artwallet\VaultRbac\Api\Dto\TemporaryGrantData $grant, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\AssignmentResult temporaryPermission(\Illuminate\Database\Eloquent\Model $model, \Artwallet\VaultRbac\Models\Permission|string|int $permission, string|int $tenantId, \Artwallet\VaultRbac\Api\Dto\TemporaryGrantData $grant, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\ApprovalSubmissionResult submitApproval(\Illuminate\Database\Eloquent\Model $subject, \Artwallet\VaultRbac\Models\Role|string|int $role, string|int $tenantId, string|int $requesterId, string|int|null $teamId = null, ?array $context = null)
 * @method static \Artwallet\VaultRbac\Api\Dto\ApprovalDecisionResult approve(string|int $approvalRequestId, string|int $approverId)
 * @method static \Artwallet\VaultRbac\Api\Dto\ApprovalDecisionResult reject(string|int $approvalRequestId, string|int $approverId)
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \Artwallet\VaultRbac\Api\Dto\AuditSummary> auditSummariesForTenant(string|int $tenantId, int $perPage = 50)
 * @method static \Artwallet\VaultRbac\Api\Dto\CacheWarmupResult warmPermissionCache(\Artwallet\VaultRbac\Api\Dto\CacheWarmTarget $target)
 * @method static \Artwallet\VaultRbac\Api\Dto\CacheFlushResult flushPermissionCache(\Artwallet\VaultRbac\Api\Dto\CacheWarmTarget $target, bool $bumpVersions = true)
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

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
 * @method static AssignmentResult assignRole(Model $model, Role|string|int $role, string|int $tenantId, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static AssignmentResult revokeRole(Model $model, Role|string|int $role, string|int $tenantId, string|int|null $teamId = null)
 * @method static AssignmentResult syncRoles(Model $model, array $roles, string|int $tenantId, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static AssignmentResult givePermission(Model $model, Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, string $effect = 'allow', string|int|null $assignedBy = null)
 * @method static AssignmentResult revokePermission(Model $model, Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, ?string $effect = null)
 * @method static AssignmentResult syncPermissions(Model $model, array $permissions, string|int $tenantId, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static AssignmentResult givePermissionTo(Model $model, Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, string $effect = 'allow', string|int|null $assignedBy = null)
 * @method static AssignmentResult revokePermissionTo(Model $model, Permission|string|int $permission, string|int $tenantId, string|int|null $teamId = null, ?string $effect = null)
 * @method static AssignmentResult temporaryRole(Model $model, Role|string|int $role, string|int $tenantId, TemporaryGrantData $grant, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static AssignmentResult temporaryPermission(Model $model, Permission|string|int $permission, string|int $tenantId, TemporaryGrantData $grant, string|int|null $teamId = null, string|int|null $assignedBy = null)
 * @method static ApprovalSubmissionResult submitApproval(Model $subject, Role|string|int $role, string|int $tenantId, string|int $requesterId, string|int|null $teamId = null, ?array $context = null)
 * @method static ApprovalDecisionResult approve(string|int $approvalRequestId, string|int $approverId)
 * @method static ApprovalDecisionResult reject(string|int $approvalRequestId, string|int $approverId)
 * @method static LengthAwarePaginator<int, AuditSummary> auditSummariesForTenant(string|int $tenantId, int $perPage = 50)
 * @method static CacheWarmupResult warmPermissionCache(CacheWarmTarget $target)
 * @method static CacheFlushResult flushPermissionCache(CacheWarmTarget $target, bool $bumpVersions = true)
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

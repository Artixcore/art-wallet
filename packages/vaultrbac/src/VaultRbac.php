<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac;

use Artwallet\VaultRbac\Api\AuthorizationQuery;
use Artwallet\VaultRbac\Api\Dto\ApprovalDecisionResult;
use Artwallet\VaultRbac\Api\Dto\ApprovalSubmissionResult;
use Artwallet\VaultRbac\Api\Dto\AssignmentResult;
use Artwallet\VaultRbac\Api\Dto\AuditSummary;
use Artwallet\VaultRbac\Api\Dto\CacheFlushResult;
use Artwallet\VaultRbac\Api\Dto\CacheWarmTarget;
use Artwallet\VaultRbac\Api\Dto\CacheWarmupResult;
use Artwallet\VaultRbac\Api\Dto\TemporaryGrantData;
use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\ApprovalWorkflowInterface;
use Artwallet\VaultRbac\Contracts\AssignmentServiceInterface;
use Artwallet\VaultRbac\Contracts\AuditLogRepository;
use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\AuthorizationRepository;
use Artwallet\VaultRbac\Contracts\PermissionCacheAdminInterface;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Contracts\TemporaryGrantServiceInterface;
use Artwallet\VaultRbac\Models\AuditLog;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Application-facing entry for authorization checks and assignments (Facade root).
 */
final class VaultRbac
{
    public function __construct(
        private readonly PermissionResolverInterface $resolver,
        private readonly AuthorizationContextFactory $contextFactory,
        private readonly AssignmentServiceInterface $assignments,
        private readonly AuthorizationRepository $authorizationRepository,
        private readonly ConfigRepository $config,
        private readonly ApprovalWorkflowInterface $approvalWorkflow,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly PermissionCacheAdminInterface $permissionCacheAdmin,
        private readonly TemporaryGrantServiceInterface $temporaryGrantService,
        private readonly ?PermissionCacheVersionRepository $permissionCacheVersionRepository = null,
    ) {}

    /**
     * Fluent, immutable authorization query builder.
     */
    public function query(): AuthorizationQuery
    {
        return AuthorizationQuery::make(
            $this->resolver,
            $this->contextFactory,
            $this->config,
            $this->permissionCacheVersionRepository,
        );
    }

    public function forUser(?Authenticatable $user): AuthorizationQuery
    {
        return $this->query()->forUser($user);
    }

    public function check(string|\Stringable $ability, ?object $resource = null): bool
    {
        return $this->resolver->authorize(
            $this->contextFactory->make(),
            $ability,
            $resource,
        );
    }

    public function can(string|\Stringable $ability, ?object $resource = null): bool
    {
        return $this->check($ability, $resource);
    }

    /**
     * Authorize using an explicit context (queue jobs, tests, sub-requests).
     */
    public function checkFor(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return $this->resolver->authorize($context, $ability, $resource);
    }

    public function canFor(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return $this->checkFor($context, $ability, $resource);
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function checkAny(array $abilities, ?object $resource = null): bool
    {
        $context = $this->contextFactory->make();
        foreach ($abilities as $ability) {
            if ($this->resolver->authorize($context, $ability, $resource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function canAny(array $abilities, ?object $resource = null): bool
    {
        return $this->checkAny($abilities, $resource);
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function checkAll(array $abilities, ?object $resource = null): bool
    {
        $context = $this->contextFactory->make();
        foreach ($abilities as $ability) {
            if (! $this->resolver->authorize($context, $ability, $resource)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function canAll(array $abilities, ?object $resource = null): bool
    {
        return $this->checkAll($abilities, $resource);
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function checkAnyFor(AuthorizationContext $context, array $abilities, ?object $resource = null): bool
    {
        foreach ($abilities as $ability) {
            if ($this->resolver->authorize($context, $ability, $resource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function canAnyFor(AuthorizationContext $context, array $abilities, ?object $resource = null): bool
    {
        return $this->checkAnyFor($context, $abilities, $resource);
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function checkAllFor(AuthorizationContext $context, array $abilities, ?object $resource = null): bool
    {
        foreach ($abilities as $ability) {
            if (! $this->resolver->authorize($context, $ability, $resource)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string|\Stringable>  $abilities
     */
    public function canAllFor(AuthorizationContext $context, array $abilities, ?object $resource = null): bool
    {
        return $this->checkAllFor($context, $abilities, $resource);
    }

    /**
     * Read-only: explicit tenant slice (does not rely on hidden globals for the tenant argument).
     */
    public function canInTenant(
        ?Authenticatable $user,
        string|int $tenantId,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        if (! $user instanceof Model) {
            return false;
        }

        $ctx = $this->contextFactory->makeFor($user)->withTenant($tenantId);

        return $this->resolver->authorize($ctx, $ability, $resource);
    }

    /**
     * Read-only: fully explicit context.
     */
    public function canInContext(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        return $this->resolver->authorize($context, $ability, $resource);
    }

    /**
     * Direct role assignment by name (does not expand hierarchy).
     */
    public function hasRole(
        string|\Stringable $roleName,
        string|int|null $tenantId = null,
        string|int|null $teamId = null,
    ): bool {
        $context = $this->contextFactory->make();
        $user = $context->user;
        if (! $user instanceof Model) {
            return false;
        }

        $resolvedTenant = $tenantId ?? $context->tenantId ?? $this->config->get('vaultrbac.default_tenant_id');
        if ($resolvedTenant === null && $this->config->get('vaultrbac.require_tenant_context', true)) {
            return false;
        }
        if ($resolvedTenant === null) {
            return false;
        }

        $resolvedTeam = $teamId ?? $context->teamId;

        return $this->authorizationRepository->userHasActiveRoleNamed(
            $user,
            trim((string) $roleName),
            $resolvedTenant,
            $resolvedTeam,
        );
    }

    public function assignRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): AssignmentResult {
        $this->assertWritableSubject($model);
        $this->assignments->assignRole($model, $role, $tenantId, $teamId, $assignedBy);

        return AssignmentResult::forOperation('assign_role', $tenantId, $model->getMorphClass(), $model->getKey());
    }

    public function revokeRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
    ): AssignmentResult {
        $this->assertWritableSubject($model);
        $this->assignments->revokeRole($model, $role, $tenantId, $teamId);

        return AssignmentResult::forOperation('revoke_role', $tenantId, $model->getMorphClass(), $model->getKey());
    }

    /**
     * @param  list<Role|string|int>  $roles
     */
    public function syncRoles(
        Model $model,
        array $roles,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): AssignmentResult {
        $this->assertWritableSubject($model);
        $this->assignments->syncRoles($model, $roles, $tenantId, $teamId, $assignedBy);

        return AssignmentResult::forOperation('sync_roles', $tenantId, $model->getMorphClass(), $model->getKey());
    }

    public function givePermission(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        string $effect = 'allow',
        string|int|null $assignedBy = null,
    ): AssignmentResult {
        $this->assertWritableSubject($model);
        $this->assignments->givePermissionTo($model, $permission, $tenantId, $teamId, $effect, $assignedBy);

        return AssignmentResult::forOperation('give_permission', $tenantId, $model->getMorphClass(), $model->getKey());
    }

    public function revokePermission(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        ?string $effect = null,
    ): AssignmentResult {
        $this->assertWritableSubject($model);
        $this->assignments->revokePermissionTo($model, $permission, $tenantId, $teamId, $effect);

        return AssignmentResult::forOperation('revoke_permission', $tenantId, $model->getMorphClass(), $model->getKey());
    }

    /**
     * @param  list<Permission|string|int>  $permissions
     */
    public function syncPermissions(
        Model $model,
        array $permissions,
        string|int $tenantId,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): AssignmentResult {
        $this->assertWritableSubject($model);
        $this->assignments->syncPermissions($model, $permissions, $tenantId, $teamId, $assignedBy);

        return AssignmentResult::forOperation('sync_permissions', $tenantId, $model->getMorphClass(), $model->getKey());
    }

    /**
     * Backwards-compatible alias for {@see givePermission()}.
     */
    public function givePermissionTo(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        string $effect = 'allow',
        string|int|null $assignedBy = null,
    ): AssignmentResult {
        return $this->givePermission($model, $permission, $tenantId, $teamId, $effect, $assignedBy);
    }

    /**
     * Backwards-compatible alias for {@see revokePermission()}.
     */
    public function revokePermissionTo(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        ?string $effect = null,
    ): AssignmentResult {
        return $this->revokePermission($model, $permission, $tenantId, $teamId, $effect);
    }

    public function temporaryRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        TemporaryGrantData $grant,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): AssignmentResult {
        return $this->temporaryGrantService->grantTemporaryRole($model, $role, $tenantId, $grant, $teamId, $assignedBy);
    }

    public function temporaryPermission(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        TemporaryGrantData $grant,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): AssignmentResult {
        return $this->temporaryGrantService->grantTemporaryPermission($model, $permission, $tenantId, $grant, $teamId, $assignedBy);
    }

    public function submitApproval(
        Model $subject,
        Role|string|int $role,
        string|int $tenantId,
        string|int $requesterId,
        string|int|null $teamId = null,
        ?array $context = null,
    ): ApprovalSubmissionResult {
        $request = $this->approvalWorkflow->requestRoleAssignment($subject, $role, $tenantId, $requesterId, $teamId, $context);

        return new ApprovalSubmissionResult($request);
    }

    public function approve(string|int $approvalRequestId, string|int $approverId): ApprovalDecisionResult
    {
        $this->approvalWorkflow->approve($approvalRequestId, $approverId);

        return new ApprovalDecisionResult($approvalRequestId, true);
    }

    public function reject(string|int $approvalRequestId, string|int $approverId): ApprovalDecisionResult
    {
        $this->approvalWorkflow->reject($approvalRequestId, $approverId);

        return new ApprovalDecisionResult($approvalRequestId, false);
    }

    /**
     * Read-only: recent audit rows as summaries for a tenant.
     *
     * @return LengthAwarePaginator<int, AuditSummary>
     */
    public function auditSummariesForTenant(string|int $tenantId, int $perPage = 50): LengthAwarePaginator
    {
        $page = $this->auditLogRepository->paginateRecentForTenant($tenantId, $perPage);

        return $page->through(fn (AuditLog $row): AuditSummary => $this->mapAuditSummary($row));
    }

    public function warmPermissionCache(CacheWarmTarget $target): CacheWarmupResult
    {
        return $this->permissionCacheAdmin->warm($target);
    }

    public function flushPermissionCache(CacheWarmTarget $target, bool $bumpVersions = true): CacheFlushResult
    {
        return $this->permissionCacheAdmin->flush($target, $bumpVersions);
    }

    private function mapAuditSummary(AuditLog $row): AuditSummary
    {
        $occurred = $row->occurred_at;

        return new AuditSummary(
            id: $row->getKey(),
            occurredAtIso8601: $occurred !== null ? $occurred->toIso8601String() : '',
            tenantId: $row->tenant_id,
            action: $row->action,
            actorType: $row->actor_type,
            actorId: $row->actor_id,
            subjectType: $row->subject_type,
            subjectId: $row->subject_id,
            requestId: $row->request_id,
        );
    }

    private function assertWritableSubject(Model $model): void
    {
        if ($model->getKey() === null) {
            throw new InvalidArgumentException('Subject model must be persisted before RBAC assignment.');
        }
    }
}

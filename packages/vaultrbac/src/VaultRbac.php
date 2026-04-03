<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\AssignmentServiceInterface;
use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\AuthorizationRepository;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;

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
    ) {}

    public function check(string|\Stringable $ability, ?object $resource = null): bool
    {
        return $this->resolver->authorize(
            $this->contextFactory->make(),
            $ability,
            $resource,
        );
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
    ): void {
        $this->assignments->assignRole($model, $role, $tenantId, $teamId, $assignedBy);
    }

    public function revokeRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        string|int|null $teamId = null,
    ): void {
        $this->assignments->revokeRole($model, $role, $tenantId, $teamId);
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
    ): void {
        $this->assignments->syncRoles($model, $roles, $tenantId, $teamId, $assignedBy);
    }

    public function givePermissionTo(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        string $effect = 'allow',
        string|int|null $assignedBy = null,
    ): void {
        $this->assignments->givePermissionTo($model, $permission, $tenantId, $teamId, $effect, $assignedBy);
    }

    public function revokePermissionTo(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        string|int|null $teamId = null,
        ?string $effect = null,
    ): void {
        $this->assignments->revokePermissionTo($model, $permission, $tenantId, $teamId, $effect);
    }
}

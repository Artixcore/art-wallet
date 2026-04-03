<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Resolvers;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\AuthorizationRepository;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Contracts\RoleHierarchyProvider;
use Artwallet\VaultRbac\Contracts\SuperUserGuard;
use Artwallet\VaultRbac\Enums\PermissionEffect;
use Artwallet\VaultRbac\Enums\RoleStatus;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

final class DatabasePermissionResolver implements PermissionResolverInterface
{
    public function __construct(
        private readonly AuthorizationRepository $repository,
        private readonly SuperUserGuard $superUserGuard,
        private readonly RoleHierarchyProvider $hierarchy,
    ) {}

    public function authorize(
        AuthorizationContext $context,
        string|\Stringable $ability,
        ?object $resource = null,
    ): bool {
        $user = $context->user;
        if (! $user instanceof Model) {
            return false;
        }

        $tenantId = $context->tenantId ?? Config::get('vaultrbac.default_tenant_id');
        if ($tenantId === null && Config::get('vaultrbac.require_tenant_context', true)) {
            return false;
        }
        if ($tenantId === null) {
            return false;
        }

        if ($this->superUserGuard->allowsPrivilegedBypass($context)) {
            return true;
        }

        $abilityName = trim((string) $ability);
        if ($abilityName === '') {
            return false;
        }

        $targetIds = $this->repository->permissionIdsForAbility($abilityName, $tenantId);
        if ($targetIds === [] && Config::get('vaultrbac.require_permission_definition', true)) {
            return false;
        }
        if ($targetIds === []) {
            return false;
        }

        $idSet = [];
        foreach ($targetIds as $id) {
            $idSet[(string) $id] = true;
        }

        $teamId = $context->teamId;

        $directAllows = false;
        foreach ($this->repository->directModelPermissions($user, $tenantId, $teamId) as $assignment) {
            $pid = (string) $assignment->permission_id;
            if (! isset($idSet[$pid])) {
                continue;
            }
            if ($assignment->effect === PermissionEffect::Deny) {
                return false;
            }
            if ($assignment->effect === PermissionEffect::Allow) {
                $directAllows = true;
            }
        }

        if ($directAllows) {
            return true;
        }

        $effectiveRoleIds = $this->effectiveActiveRoleIds($user, $tenantId, $teamId);
        if ($effectiveRoleIds === []) {
            return false;
        }

        $granted = $this->repository->permissionIdsGrantedToRoles($effectiveRoleIds, $tenantId);
        foreach ($granted as $permissionId) {
            if (isset($idSet[(string) $permissionId])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int|string>
     */
    private function effectiveActiveRoleIds(Model $user, string|int $tenantId, string|int|null $teamId): array
    {
        $roleClass = Config::get('vaultrbac.models.role');
        if (! is_string($roleClass) || ! class_exists($roleClass)) {
            return [];
        }

        $seed = [];
        foreach ($this->repository->modelRoles($user, $tenantId, $teamId) as $assignment) {
            $role = $assignment->role;
            if (! $role instanceof Role) {
                continue;
            }
            if ($role->activation_state !== RoleStatus::Active) {
                continue;
            }
            if ($role->tenant_id !== null && (string) $role->tenant_id !== (string) $tenantId) {
                continue;
            }

            $seed[] = $assignment->role_id;
            foreach ($this->hierarchy->ancestors($assignment->role_id, $tenantId) as $ancestorId) {
                $seed[] = $ancestorId;
            }
        }

        if ($seed === []) {
            return [];
        }

        $unique = array_unique(array_map(static fn ($id): string => (string) $id, $seed));

        return $roleClass::query()
            ->whereIn('id', $unique)
            ->where('activation_state', RoleStatus::Active->value)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->pluck('id')
            ->map(static fn ($id): string|int => $id)
            ->values()
            ->all();
    }
}

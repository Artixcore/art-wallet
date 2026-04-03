<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Api\Dto\AssignmentResult;
use Artwallet\VaultRbac\Api\Dto\TemporaryGrantData;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Database\Eloquent\Model;

interface TemporaryGrantServiceInterface
{
    /**
     * Assigns role with {@see ModelRole::expires_at} set from grant window (authorization-aware).
     *
     * @throws \Artwallet\VaultRbac\Exceptions\InvalidAssignmentException
     */
    public function grantTemporaryRole(
        Model $model,
        Role|string|int $role,
        string|int $tenantId,
        TemporaryGrantData $grant,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): AssignmentResult;

    /**
     * Grants direct allow permission with expiry on {@see ModelPermission::expires_at}.
     *
     * @throws \Artwallet\VaultRbac\Exceptions\InvalidAssignmentException
     */
    public function grantTemporaryPermission(
        Model $model,
        Permission|string|int $permission,
        string|int $tenantId,
        TemporaryGrantData $grant,
        string|int|null $teamId = null,
        string|int|null $assignedBy = null,
    ): AssignmentResult;
}

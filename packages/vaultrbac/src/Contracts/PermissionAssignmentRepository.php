<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Models\ModelPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface PermissionAssignmentRepository
{
    /**
     * @return Collection<int, ModelPermission>
     */
    public function listActiveForSubject(
        Model $subject,
        string|int $tenantId,
        string|int|null $teamId,
    ): Collection;
}

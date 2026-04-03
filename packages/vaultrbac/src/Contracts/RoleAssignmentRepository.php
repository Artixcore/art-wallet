<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Models\ModelRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface RoleAssignmentRepository
{
    /**
     * Active assignments for the subject in the tenant (respects {@code team_key} semantics when {@code $teamId} null = global only).
     *
     * @return Collection<int, ModelRole>
     */
    public function listActiveForSubject(
        Model $subject,
        string|int $tenantId,
        string|int|null $teamId,
    ): Collection;
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\RoleAssignmentRepository;
use Artwallet\VaultRbac\Models\ModelRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class EloquentRoleAssignmentRepository implements RoleAssignmentRepository
{
    public function listActiveForSubject(
        Model $subject,
        string|int $tenantId,
        string|int|null $teamId,
    ): Collection {
        return ModelRole::query()
            ->where('model_type', $subject->getMorphClass())
            ->where('model_id', $subject->getKey())
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($teamId): void {
                if ($teamId === null) {
                    $query->where('team_key', 0);
                } else {
                    $query->whereIn('team_key', [0, (int) $teamId]);
                }
            })
            ->whereNull('suspended_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('role')
            ->get();
    }
}

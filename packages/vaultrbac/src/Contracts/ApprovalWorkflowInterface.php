<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Models\ApprovalRequest;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Database\Eloquent\Model;

interface ApprovalWorkflowInterface
{
    /**
     * Queue a privileged role assignment for approval (does not assign immediately).
     */
    public function requestRoleAssignment(
        Model $subject,
        Role|string|int $role,
        string|int $tenantId,
        string|int $requesterId,
        string|int|null $teamId = null,
        ?array $context = null,
    ): ApprovalRequest;

    public function approve(string|int $approvalRequestId, string|int $approverId): void;

    public function reject(string|int $approvalRequestId, string|int $approverId): void;
}

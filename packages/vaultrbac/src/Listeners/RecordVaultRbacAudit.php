<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Listeners;

use Artwallet\VaultRbac\Audit\AuditRecord;
use Artwallet\VaultRbac\Contracts\AuditSink;
use Artwallet\VaultRbac\Events\PermissionGranted;
use Artwallet\VaultRbac\Events\PermissionRevoked;
use Artwallet\VaultRbac\Events\RoleAssigned;
use Artwallet\VaultRbac\Events\RoleRevoked;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Auth;

final class RecordVaultRbacAudit
{
    public function __construct(
        private readonly AuditSink $sink,
        private readonly ConfigRepository $config,
    ) {}

    public function handleRoleAssigned(RoleAssigned $event): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        $actor = Auth::user();

        $this->sink->write(new AuditRecord(
            action: 'role.assigned',
            payload: [
                'assignment_id' => $event->assignment->getKey(),
                'role_id' => $event->role->getKey(),
                'role_name' => $event->role->name,
                'team_id' => $event->teamId,
            ],
            correlationId: null,
            tenantId: $event->tenantId,
            actorType: $actor?->getMorphClass(),
            actorId: $actor?->getKey(),
            subjectType: $event->model->getMorphClass(),
            subjectId: $event->model->getKey(),
            targetType: $event->role->getMorphClass(),
            targetId: $event->role->getKey(),
        ));
    }

    public function handleRoleRevoked(RoleRevoked $event): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        $actor = Auth::user();

        $this->sink->write(new AuditRecord(
            action: 'role.revoked',
            payload: [
                'role_id' => $event->role->getKey(),
                'role_name' => $event->role->name,
                'team_id' => $event->teamId,
            ],
            correlationId: null,
            tenantId: $event->tenantId,
            actorType: $actor?->getMorphClass(),
            actorId: $actor?->getKey(),
            subjectType: $event->model->getMorphClass(),
            subjectId: $event->model->getKey(),
            targetType: $event->role->getMorphClass(),
            targetId: $event->role->getKey(),
        ));
    }

    public function handlePermissionGranted(PermissionGranted $event): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        $actor = Auth::user();

        $this->sink->write(new AuditRecord(
            action: 'permission.granted',
            payload: [
                'assignment_id' => $event->assignment->getKey(),
                'effect' => $event->effect,
                'permission_id' => $event->permission->getKey(),
                'permission_name' => $event->permission->name,
                'team_id' => $event->teamId,
            ],
            correlationId: null,
            tenantId: $event->tenantId,
            actorType: $actor?->getMorphClass(),
            actorId: $actor?->getKey(),
            subjectType: $event->model->getMorphClass(),
            subjectId: $event->model->getKey(),
            targetType: $event->permission->getMorphClass(),
            targetId: $event->permission->getKey(),
        ));
    }

    public function handlePermissionRevoked(PermissionRevoked $event): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        $actor = Auth::user();

        $this->sink->write(new AuditRecord(
            action: 'permission.revoked',
            payload: [
                'effect' => $event->effect,
                'permission_id' => $event->permission->getKey(),
                'permission_name' => $event->permission->name,
                'team_id' => $event->teamId,
            ],
            correlationId: null,
            tenantId: $event->tenantId,
            actorType: $actor?->getMorphClass(),
            actorId: $actor?->getKey(),
            subjectType: $event->model->getMorphClass(),
            subjectId: $event->model->getKey(),
            targetType: $event->permission->getMorphClass(),
            targetId: $event->permission->getKey(),
        ));
    }

    private function shouldRecord(): bool
    {
        if (! $this->config->get('vaultrbac.audit.enabled', true)) {
            return false;
        }

        return (bool) $this->config->get('vaultrbac.audit.register_listeners', true);
    }
}

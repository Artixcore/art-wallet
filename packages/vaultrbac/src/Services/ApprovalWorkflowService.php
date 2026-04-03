<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Services;

use Artwallet\VaultRbac\Contracts\ApprovalWorkflowInterface;
use Artwallet\VaultRbac\Contracts\AssignmentServiceInterface;
use Artwallet\VaultRbac\Exceptions\EncryptionException;
use Artwallet\VaultRbac\Exceptions\InvalidAssignmentException;
use Artwallet\VaultRbac\Models\ApprovalRequest;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

final class ApprovalWorkflowService implements ApprovalWorkflowInterface
{
    public function __construct(
        private readonly AssignmentServiceInterface $assignments,
        private readonly Encrypter $encrypter,
        private readonly ConfigRepository $config,
    ) {}

    public function requestRoleAssignment(
        Model $subject,
        Role|string|int $role,
        string|int $tenantId,
        string|int $requesterId,
        string|int|null $teamId = null,
        ?array $context = null,
    ): ApprovalRequest {
        $roleDescriptor = $this->roleDescriptor($role);

        $payload = [
            'context' => $context ?? [],
            'kind' => 'assign_role',
            'role' => $roleDescriptor,
            'team_id' => $teamId,
            'tenant_id' => $tenantId,
            'v' => 1,
        ];

        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $stored = $this->encryptsApprovalPayload()
            ? $this->encrypter->encryptString($json)
            : $json;

        return ApprovalRequest::query()->create([
            'correlation_id' => Str::uuid()->toString(),
            'decided_at' => null,
            'decided_by' => null,
            'payload' => $stored,
            'requester_id' => $requesterId,
            'required_approvers' => null,
            'status' => 'pending',
            'subject_id' => $subject->getKey(),
            'subject_type' => $subject->getMorphClass(),
            'tenant_id' => $tenantId,
        ]);
    }

    public function approve(string|int $approvalRequestId, string|int $approverId): void
    {
        DB::transaction(function () use ($approvalRequestId, $approverId): void {
            /** @var ApprovalRequest $request */
            $request = ApprovalRequest::query()->whereKey($approvalRequestId)->lockForUpdate()->firstOrFail();

            if ($request->status !== 'pending') {
                throw new InvalidAssignmentException('Only pending approval requests can be approved.');
            }

            $data = $this->decodePayload((string) $request->payload);
            if (($data['kind'] ?? '') !== 'assign_role') {
                throw new InvalidAssignmentException('Unsupported approval payload kind.');
            }

            $subject = $request->subject;
            if (! $subject instanceof Model) {
                throw new InvalidAssignmentException('Approval subject could not be loaded.');
            }

            $tenantId = $data['tenant_id'] ?? null;
            $teamId = $data['team_id'] ?? null;
            if ($tenantId === null) {
                throw new InvalidAssignmentException('Missing tenant_id in approval payload.');
            }

            $role = $data['role'] ?? null;
            if (! is_array($role)) {
                throw new InvalidAssignmentException('Missing role descriptor in approval payload.');
            }

            $resolvedRole = $role['id'] ?? $role['name'] ?? null;
            if ($resolvedRole === null) {
                throw new InvalidAssignmentException('Role descriptor must include id or name.');
            }

            $this->assignments->assignRole($subject, $resolvedRole, $tenantId, $teamId, $approverId);

            $request->forceFill([
                'decided_at' => now(),
                'decided_by' => $approverId,
                'status' => 'approved',
            ])->save();
        });
    }

    public function reject(string|int $approvalRequestId, string|int $approverId): void
    {
        DB::transaction(function () use ($approvalRequestId, $approverId): void {
            /** @var ApprovalRequest $request */
            $request = ApprovalRequest::query()->whereKey($approvalRequestId)->lockForUpdate()->firstOrFail();

            if ($request->status !== 'pending') {
                throw new InvalidAssignmentException('Only pending approval requests can be rejected.');
            }

            $request->forceFill([
                'decided_at' => now(),
                'decided_by' => $approverId,
                'status' => 'rejected',
            ])->save();
        });
    }

    /**
     * @return array{id?: int, name?: string}
     */
    private function roleDescriptor(Role|string|int $role): array
    {
        if ($role instanceof Role) {
            return ['id' => (int) $role->getKey()];
        }

        if (is_int($role) || (is_string($role) && ctype_digit($role))) {
            return ['id' => (int) $role];
        }

        return ['name' => (string) $role];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $payload): array
    {
        try {
            if ($this->encryptsApprovalPayload()) {
                $plain = $this->encrypter->decryptString($payload);
            } else {
                $plain = $payload;
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($plain, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (JsonException $e) {
            throw new EncryptionException('Malformed approval payload JSON.', 0, $e);
        } catch (DecryptException $e) {
            throw new EncryptionException('Unable to decrypt approval payload.', 0, $e);
        }
    }

    private function encryptsApprovalPayload(): bool
    {
        return (bool) $this->config->get('vaultrbac.encryption.approvals.encrypt_payload', true);
    }
}

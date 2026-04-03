<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Repositories;

use Artwallet\VaultRbac\Contracts\ApprovalRequestRepository;
use Artwallet\VaultRbac\Enums\ApprovalStatus;
use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Exceptions\Data\InvalidModelStateException;
use Artwallet\VaultRbac\Models\ApprovalRequest;
use Artwallet\VaultRbac\Support\PrimaryKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

final class EloquentApprovalRequestRepository implements ApprovalRequestRepository
{
    public function findById(mixed $id): ?ApprovalRequest
    {
        $key = PrimaryKey::normalize($id);

        return ApprovalRequest::query()->whereKey($key)->first();
    }

    public function getById(mixed $id): ApprovalRequest
    {
        $row = $this->findById($id);
        if ($row === null) {
            throw new EntityNotFoundException(
                'Approval request not found.',
                0,
                null,
                ApprovalRequest::class,
                $id,
            );
        }

        return $row;
    }

    public function findByCorrelationId(string $correlationId): ?ApprovalRequest
    {
        $trim = trim($correlationId);
        if ($trim === '') {
            return null;
        }

        return ApprovalRequest::query()->where('correlation_id', $trim)->first();
    }

    public function getByCorrelationId(string $correlationId): ApprovalRequest
    {
        $row = $this->findByCorrelationId($correlationId);
        if ($row === null) {
            throw new EntityNotFoundException(
                'Approval request not found.',
                0,
                null,
                ApprovalRequest::class,
                $correlationId,
            );
        }

        return $row;
    }

    public function listPendingForTenant(string|int $tenantId): Collection
    {
        return ApprovalRequest::query()
            ->forTenant($tenantId)
            ->pending()
            ->orderByDesc('id')
            ->get();
    }

    public function transitionTo(ApprovalRequest $request, ApprovalStatus $next): void
    {
        $current = $request->status;
        if (! $current instanceof ApprovalStatus) {
            throw new InvalidModelStateException(
                'Approval request has no valid status.',
                0,
                null,
                ['id' => $request->getKey()],
            );
        }

        if ($current !== ApprovalStatus::Pending) {
            throw new InvalidModelStateException(
                'Only pending approval requests can transition.',
                0,
                null,
                ['id' => $request->getKey(), 'status' => $current->value],
            );
        }

        if ($next === ApprovalStatus::Pending) {
            throw new InvalidModelStateException(
                'Cannot transition to pending.',
                0,
                null,
                ['id' => $request->getKey()],
            );
        }

        $request->status = $next;
        $request->decided_at = now();

        $this->persist($request);
    }

    public function persist(ApprovalRequest $request): void
    {
        try {
            $request->save();
        } catch (QueryException $e) {
            PersistenceExceptionMapper::mapQueryException($e, 'approval request persist');
        }
    }
}

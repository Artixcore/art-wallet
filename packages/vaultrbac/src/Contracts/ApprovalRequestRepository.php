<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Enums\ApprovalStatus;
use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Exceptions\Data\InvalidModelStateException;
use Artwallet\VaultRbac\Exceptions\Data\UnsupportedIdentifierTypeException;
use Artwallet\VaultRbac\Models\ApprovalRequest;
use Illuminate\Support\Collection;

interface ApprovalRequestRepository
{
    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public function findById(mixed $id): ?ApprovalRequest;

    /**
     * @throws EntityNotFoundException
     * @throws UnsupportedIdentifierTypeException
     */
    public function getById(mixed $id): ApprovalRequest;

    public function findByCorrelationId(string $correlationId): ?ApprovalRequest;

    /**
     * @throws EntityNotFoundException
     */
    public function getByCorrelationId(string $correlationId): ApprovalRequest;

    /**
     * @return Collection<int, ApprovalRequest>
     */
    public function listPendingForTenant(string|int $tenantId): Collection;

    /**
     * Valid transitions: pending → approved|rejected|cancelled only.
     *
     * @throws InvalidModelStateException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DuplicateEntityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DataIntegrityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\RepositoryException
     */
    public function transitionTo(ApprovalRequest $request, ApprovalStatus $next): void;

    /**
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DuplicateEntityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\DataIntegrityException
     * @throws \Artwallet\VaultRbac\Exceptions\Data\RepositoryException
     */
    public function persist(ApprovalRequest $request): void;
}

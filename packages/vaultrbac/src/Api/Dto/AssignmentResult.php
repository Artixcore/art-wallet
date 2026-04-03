<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

/**
 * Output-only: result of a successful assignment mutation (failures throw before return).
 */
final readonly class AssignmentResult
{
    public function __construct(
        public string $operation,
        public string|int $tenantId,
        public string $subjectType,
        public string|int $subjectId,
    ) {}

    public static function forOperation(
        string $operation,
        string|int $tenantId,
        string $subjectType,
        string|int $subjectId,
    ): self {
        return new self($operation, $tenantId, $subjectType, $subjectId);
    }
}

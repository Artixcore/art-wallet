<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

/**
 * Output-only: redacted audit row for application dashboards.
 */
final readonly class AuditSummary
{
    public function __construct(
        public string|int $id,
        public string $occurredAtIso8601,
        public string|int|null $tenantId,
        public ?string $action,
        public ?string $actorType,
        public string|int|null $actorId,
        public ?string $subjectType,
        public string|int|null $subjectId,
        public ?string $requestId,
    ) {}
}

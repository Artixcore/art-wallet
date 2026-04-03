<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Audit;

use Artwallet\VaultRbac\Contracts\AuditSink;
use Artwallet\VaultRbac\Models\AuditEvent;

/**
 * Structured audit payload for {@see AuditSink}.
 */
final readonly class AuditRecord
{
    /**
     * @param  array<string, mixed>  $payload  Stored as JSON diff on {@see AuditEvent}.
     */
    public function __construct(
        public string $action,
        public array $payload = [],
        public ?string $correlationId = null,
        public string|int|null $tenantId = null,
        public ?string $actorType = null,
        public string|int|null $actorId = null,
        public ?string $subjectType = null,
        public string|int|null $subjectId = null,
        public ?string $targetType = null,
        public string|int|null $targetId = null,
    ) {}
}

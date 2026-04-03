<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Audit;

use Artwallet\VaultRbac\Contracts\AuditSink;

/**
 * Structured audit payload for {@see AuditSink}.
 * Phase 1 minimal fields; expanded when the audit layer is wired.
 */
final readonly class AuditRecord
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $action,
        public array $payload = [],
        public ?string $correlationId = null,
    ) {}
}

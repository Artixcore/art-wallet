<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Audit;

use Artwallet\VaultRbac\Contracts\AuditSink;

final class NullAuditSink implements AuditSink
{
    public function write(AuditRecord $record): void
    {
        // No-op until a real sink is bound (Phase 5+).
    }
}

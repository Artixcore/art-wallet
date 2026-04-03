<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

use Artwallet\VaultRbac\Audit\AuditRecord;

/**
 * Append-only audit sink (database, syslog, SIEM). Writers must not mutate
 * or delete historical records.
 */
interface AuditSink
{
    public function write(AuditRecord $record): void;
}

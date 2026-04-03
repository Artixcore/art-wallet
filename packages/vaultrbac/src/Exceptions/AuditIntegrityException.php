<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions;

/**
 * Audit chain verification failed or append-only constraint was violated.
 */
final class AuditIntegrityException extends VaultRbacException {}

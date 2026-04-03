<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions\Data;

use Artwallet\VaultRbac\Exceptions\VaultRbacException;
use Throwable;

/**
 * Thrown when a foreign key or integrity constraint fails at the database layer.
 */
class DataIntegrityException extends VaultRbacException
{
    public function __construct(
        string $message = 'The operation would break data integrity constraints.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

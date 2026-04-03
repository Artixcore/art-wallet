<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions\Data;

use Artwallet\VaultRbac\Exceptions\VaultRbacException;
use Throwable;

/**
 * Thrown when a unique database constraint is violated on insert/update.
 */
class DuplicateEntityException extends VaultRbacException
{
    public function __construct(
        string $message = 'A record with this key already exists.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

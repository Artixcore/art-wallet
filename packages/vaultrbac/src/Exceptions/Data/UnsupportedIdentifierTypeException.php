<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions\Data;

use Artwallet\VaultRbac\Exceptions\VaultRbacException;
use Throwable;

/**
 * Thrown when a repository receives an id value incompatible with configured key strategy.
 */
class UnsupportedIdentifierTypeException extends VaultRbacException
{
    public function __construct(
        string $message = 'The given identifier type or format is not supported.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

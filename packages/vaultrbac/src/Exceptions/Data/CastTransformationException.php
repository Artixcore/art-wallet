<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions\Data;

use Artwallet\VaultRbac\Exceptions\VaultRbacException;
use Throwable;

/**
 * Thrown when an Eloquent cast cannot transform stored data to/from the expected shape.
 */
class CastTransformationException extends VaultRbacException
{
    public function __construct(
        string $message = 'Stored value could not be transformed by the attribute cast.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

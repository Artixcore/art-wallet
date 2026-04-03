<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions\Data;

use Artwallet\VaultRbac\Exceptions\VaultRbacException;
use Throwable;

/**
 * Thrown when encrypted metadata cannot be decrypted or decoded safely.
 */
class InvalidEncryptedPayloadException extends VaultRbacException
{
    public function __construct(
        string $message = 'Encrypted payload could not be processed.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

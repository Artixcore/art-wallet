<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions\Data;

use Artwallet\VaultRbac\Exceptions\VaultRbacException;
use Throwable;

/**
 * Thrown when invariants fail before a write (missing tenant, invalid enum value, etc.).
 */
class InvalidModelStateException extends VaultRbacException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'The record is in an invalid state for this operation.',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}

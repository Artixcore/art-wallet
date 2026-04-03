<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions\Data;

use Artwallet\VaultRbac\Exceptions\VaultRbacException;
use Throwable;

/**
 * Thrown by repository {@code get*} methods when the row does not exist.
 */
class EntityNotFoundException extends VaultRbacException
{
    public function __construct(
        string $message = 'The requested record was not found.',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly ?string $entityType = null,
        private readonly string|int|null $identifier = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function entityType(): ?string
    {
        return $this->entityType;
    }

    public function identifier(): string|int|null
    {
        return $this->identifier;
    }
}

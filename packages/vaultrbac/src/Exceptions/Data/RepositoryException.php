<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions\Data;

use Artwallet\VaultRbac\Exceptions\VaultRbacException;
use Throwable;

/**
 * Wraps unexpected persistence errors from the repository layer.
 */
class RepositoryException extends VaultRbacException
{
    /**
     * @param  array<string, mixed>  $context  Safe diagnostic context for logs (no secrets).
     */
    public function __construct(
        string $message = 'A persistence error occurred.',
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

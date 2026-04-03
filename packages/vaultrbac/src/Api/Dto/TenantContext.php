<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

use InvalidArgumentException;

/**
 * Input-only: explicit tenant (and optional team) scope for authorization or assignments.
 */
final readonly class TenantContext
{
    public function __construct(
        public string|int $tenantId,
        public string|int|null $teamId = null,
    ) {
        if (is_string($this->tenantId) && trim($this->tenantId) === '') {
            throw new InvalidArgumentException('Tenant id must not be empty.');
        }
    }
}

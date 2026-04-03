<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

use InvalidArgumentException;

/**
 * Input-only: scope for permission cache warm/flush operations.
 */
final readonly class CacheWarmTarget
{
    public function __construct(
        public string|int|null $tenantId = null,
        public string|int|null $userId = null,
        public bool $allTenants = false,
    ) {
        if ($this->allTenants && ($this->tenantId !== null || $this->userId !== null)) {
            throw new InvalidArgumentException('allTenants cannot be combined with tenantId or userId.');
        }
    }
}

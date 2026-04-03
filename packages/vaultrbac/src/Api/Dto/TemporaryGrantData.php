<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Input-only: validated window for temporary role/permission grants.
 */
final readonly class TemporaryGrantData
{
    public function __construct(
        public CarbonInterface $validFrom,
        public CarbonInterface $validUntil,
        public ?string $reason = null,
        public string|int|null $approvalRequestId = null,
    ) {
        if ($this->validUntil->lessThanOrEqualTo($this->validFrom)) {
            throw new InvalidArgumentException('validUntil must be after validFrom.');
        }
    }
}

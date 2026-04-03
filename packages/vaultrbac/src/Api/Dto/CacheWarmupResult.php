<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

/**
 * Output-only: cache warm operation statistics.
 */
final readonly class CacheWarmupResult
{
    public function __construct(
        public bool $success,
        public int $entriesWarmed,
        public ?string $message = null,
    ) {}
}

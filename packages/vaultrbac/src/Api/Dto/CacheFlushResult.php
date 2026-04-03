<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

/**
 * Output-only: cache flush / version bump statistics.
 */
final readonly class CacheFlushResult
{
    public function __construct(
        public bool $success,
        public int $keysRemovedOrBumped,
        public ?string $message = null,
    ) {}
}

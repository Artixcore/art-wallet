<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api\Dto;

use Artwallet\VaultRbac\Api\PermissionDenialReason;

/**
 * Output-only: structured result of a permission check (safe to log; no stack traces).
 */
final readonly class PermissionDecision
{
    public function __construct(
        public bool $granted,
        public string $ability,
        public string|int|null $tenantId,
        public PermissionDenialReason $reason,
        public ?int $cacheVersion = null,
    ) {}

    public static function allow(string $ability, string|int|null $tenantId, ?int $cacheVersion = null): self
    {
        return new self(
            true,
            $ability,
            $tenantId,
            PermissionDenialReason::Granted,
            $cacheVersion,
        );
    }

    public static function deny(
        string $ability,
        string|int|null $tenantId,
        PermissionDenialReason $reason,
        ?int $cacheVersion = null,
    ): self {
        return new self(
            false,
            $ability,
            $tenantId,
            $reason,
            $cacheVersion,
        );
    }

    public function toBool(): bool
    {
        return $this->granted;
    }
}

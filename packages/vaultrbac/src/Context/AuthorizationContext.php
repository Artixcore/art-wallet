<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Context;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Immutable snapshot used by the permission resolver. Built per check or per
 * request depending on application caching strategy (Phase 3+).
 */
final readonly class AuthorizationContext
{
    public function __construct(
        public ?Authenticatable $user,
        public string|int|null $tenantId,
        public string|int|null $teamId,
        public ?string $sessionId,
        public ?string $deviceId,
        public ?string $environment,
    ) {}

    public function withTenant(string|int|null $tenantId): self
    {
        return new self(
            $this->user,
            $tenantId,
            $this->teamId,
            $this->sessionId,
            $this->deviceId,
            $this->environment,
        );
    }

    public function withTeam(string|int|null $teamId): self
    {
        return new self(
            $this->user,
            $this->tenantId,
            $teamId,
            $this->sessionId,
            $this->deviceId,
            $this->environment,
        );
    }

    public function guest(): bool
    {
        return $this->user === null;
    }
}

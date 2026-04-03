<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Contracts;

/**
 * Resolves the active team identifier for the current request / job context.
 */
interface TeamResolver
{
    /**
     * @return string|int|null Opaque team primary key, or null if not team-scoped.
     */
    public function resolve(): string|int|null;
}

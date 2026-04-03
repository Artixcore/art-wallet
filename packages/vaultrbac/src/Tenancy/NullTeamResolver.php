<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tenancy;

use Artwallet\VaultRbac\Contracts\TeamResolver;

final class NullTeamResolver implements TeamResolver
{
    public function resolve(): string|int|null
    {
        return null;
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tenancy;

use Artwallet\VaultRbac\Contracts\TenantResolver;

final class NullTenantResolver implements TenantResolver
{
    public function resolve(): string|int|null
    {
        return null;
    }
}

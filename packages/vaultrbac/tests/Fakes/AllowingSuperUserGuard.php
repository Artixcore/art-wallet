<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Fakes;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\SuperUserGuard;

/** Test-only: always allows privileged bypass. */
final class AllowingSuperUserGuard implements SuperUserGuard
{
    public function allowsPrivilegedBypass(AuthorizationContext $context): bool
    {
        return true;
    }
}

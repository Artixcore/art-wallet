<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Security;

use Artwallet\VaultRbac\Context\AuthorizationContext;
use Artwallet\VaultRbac\Contracts\SuperUserGuard;

final class NullSuperUserGuard implements SuperUserGuard
{
    public function allowsPrivilegedBypass(AuthorizationContext $context): bool
    {
        return false;
    }
}

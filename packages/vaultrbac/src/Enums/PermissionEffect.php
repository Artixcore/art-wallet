<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Enums;

enum PermissionEffect: string
{
    case Allow = 'allow';
    case Deny = 'deny';
}

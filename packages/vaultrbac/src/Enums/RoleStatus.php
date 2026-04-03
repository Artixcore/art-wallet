<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Enums;

enum RoleStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';
}

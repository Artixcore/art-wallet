<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Deleted = 'deleted';
}

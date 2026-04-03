<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Enums;

enum AssignmentSource: string
{
    case Direct = 'direct';
    case Inherited = 'inherited';
    case Sync = 'sync';
    case Import = 'import';
    case Delegated = 'delegated';
}

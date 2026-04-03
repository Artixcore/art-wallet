<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Enums;

/**
 * Recommended {@code scope} values for {@see \Artwallet\VaultRbac\Models\PermissionCacheVersion}.
 */
enum CacheBumpScope: string
{
    case Subjects = 'subjects';
    case Roles = 'roles';
    case Permissions = 'permissions';
    case Wildcard = 'wildcard';
}

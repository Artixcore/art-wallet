<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Enums;

/**
 * Discriminator stored in {@see \Artwallet\VaultRbac\Models\RoleExpiration::$target}.
 */
enum RoleExpirationTarget: string
{
    /** {@see \Artwallet\VaultRbac\Models\ModelRole} pivot row id */
    case ModelRole = 'model_role';

    /** {@see \Artwallet\VaultRbac\Models\ModelPermission} pivot row id */
    case ModelPermission = 'model_permission';

    /** {@see \Artwallet\VaultRbac\Models\TemporaryPermission} row id */
    case TemporaryPermission = 'temporary_permission';
}

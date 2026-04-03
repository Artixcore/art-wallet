<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Enums;

/**
 * Stable action names written to {@see \Artwallet\VaultRbac\Models\AuditLog}.
 */
enum AuditActionType: string
{
    case RoleAssigned = 'role.assigned';
    case RoleRevoked = 'role.revoked';
    case PermissionGranted = 'permission.granted';
    case PermissionRevoked = 'permission.revoked';
}

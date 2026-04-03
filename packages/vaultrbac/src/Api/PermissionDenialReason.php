<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Api;

/**
 * Machine reason for a denied permission decision (no user-facing text).
 */
enum PermissionDenialReason: string
{
    case Granted = 'granted';
    case GuestUser = 'guest_user';
    case MissingTenant = 'missing_tenant';
    case EmptyAbility = 'empty_ability';
    case ResolverDenied = 'resolver_denied';
    case StrictTenantRequired = 'strict_tenant_required';
    case InvalidSubject = 'invalid_subject';
    case VersionReadFailed = 'version_read_failed';
}

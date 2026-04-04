<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Enums;

enum ContactResolutionStatus: string
{
    case ResolvedArtwalletUser = 'resolved_artwallet_user';
    case NotFound = 'not_found';
    case PrivacyRestricted = 'privacy_restricted';
    case InvalidAddress = 'invalid_address';
    case SelfAddress = 'self_address';
    case MessagingKeyRequired = 'messaging_key_required';
    case DmRequiresApproval = 'dm_requires_approval';
}

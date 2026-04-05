<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Enums;

enum OnboardingState: string
{
    case AwaitingVaultUpload = 'awaiting_vault_upload';
    case AwaitingPassphraseAck = 'awaiting_passphrase_ack';
    case AwaitingPassphraseConfirm = 'awaiting_passphrase_confirm';
    case Completed = 'completed';
    case Failed = 'failed';
    case LockedOut = 'locked_out';

    public function allowsDashboard(): bool
    {
        return $this === self::Completed;
    }
}

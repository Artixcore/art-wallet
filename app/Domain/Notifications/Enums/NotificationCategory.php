<?php

namespace App\Domain\Notifications\Enums;

enum NotificationCategory: string
{
    case Transaction = 'transaction';
    case Security = 'security';
    case Device = 'device';
    case Recovery = 'recovery';
    case Messaging = 'messaging';
    case FileTransfer = 'file_transfer';
    case Portfolio = 'portfolio';
    case System = 'system';
}

<?php

namespace App\Domain\Notifications\Enums;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Mail = 'mail';
    case Push = 'push';
}

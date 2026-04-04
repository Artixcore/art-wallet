<?php

namespace App\Domain\Notifications\Enums;

enum NotificationSeverity: string
{
    case Success = 'success';
    case Info = 'info';
    case Warning = 'warning';
    case Danger = 'danger';
    case Critical = 'critical';
}

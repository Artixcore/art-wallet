<?php

namespace App\Domain\Settings\Exceptions;

use RuntimeException;

final class SettingsConflictException extends RuntimeException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('These settings were updated elsewhere. Refresh and try again.'));
    }
}

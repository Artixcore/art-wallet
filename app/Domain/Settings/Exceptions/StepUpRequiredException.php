<?php

namespace App\Domain\Settings\Exceptions;

use RuntimeException;

final class StepUpRequiredException extends RuntimeException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('Confirm your password to apply this security change.'));
    }
}

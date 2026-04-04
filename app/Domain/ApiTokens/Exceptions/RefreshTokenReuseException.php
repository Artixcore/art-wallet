<?php

declare(strict_types=1);

namespace App\Domain\ApiTokens\Exceptions;

use RuntimeException;

final class RefreshTokenReuseException extends RuntimeException {}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions;

use RuntimeException;

/**
 * Base exception for all package errors. Catch this only at boundaries; prefer
 * specific subclasses for control flow.
 */
class VaultRbacException extends RuntimeException {}

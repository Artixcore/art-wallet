<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions;

/**
 * Thrown when an explicit deny or policy outcome rejects access (optional;
 * many code paths will return false instead).
 */
final class AuthorizationDeniedException extends VaultRbacException {}

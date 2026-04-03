<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions;

/**
 * Tenant context is required but cannot be resolved safely.
 */
final class TenantResolutionException extends VaultRbacException {}

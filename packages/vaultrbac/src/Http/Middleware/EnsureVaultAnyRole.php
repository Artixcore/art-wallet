<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

/**
 * @deprecated Use {@see RequireAnyRoleMiddleware} or middleware alias vrb.role.any.
 */
final class EnsureVaultAnyRole extends RequireAnyRoleMiddleware {}

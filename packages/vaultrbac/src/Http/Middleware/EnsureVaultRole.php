<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

/**
 * @deprecated Use {@see RequireRoleMiddleware} or middleware alias vrb.role.
 */
final class EnsureVaultRole extends RequireRoleMiddleware {}

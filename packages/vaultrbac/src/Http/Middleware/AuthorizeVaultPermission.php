<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

/**
 * @deprecated Use {@see RequirePermissionMiddleware} or middleware alias vrb.permission.
 */
final class AuthorizeVaultPermission extends RequirePermissionMiddleware {}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\VaultRbac;
use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires an active direct assignment to the named role (see {@see VaultRbac::hasRole}).
 */
final class EnsureVaultRole
{
    public function __construct(
        private readonly VaultRbac $vault,
        private readonly ConfigRepository $config,
    ) {}

    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $this->vault->hasRole($role)) {
            abort((int) $this->config->get('vaultrbac.middleware.missing_permission_status', 403));
        }

        return $next($request);
    }
}

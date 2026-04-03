<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\VaultRbac;
use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires at least one of the pipe-separated role names (e.g. admin|editor).
 */
final class EnsureVaultAnyRole
{
    public function __construct(
        private readonly VaultRbac $vault,
        private readonly ConfigRepository $config,
    ) {}

    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $names = array_values(array_filter(array_map(trim(...), explode('|', $roles))));

        foreach ($names as $role) {
            if ($this->vault->hasRole($role)) {
                return $next($request);
            }
        }

        abort((int) $this->config->get('vaultrbac.middleware.missing_permission_status', 403));
    }
}

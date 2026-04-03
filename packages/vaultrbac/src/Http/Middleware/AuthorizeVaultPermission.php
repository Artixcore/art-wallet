<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\VaultRbac;
use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aborts when the current authorization context cannot perform the ability.
 */
final class AuthorizeVaultPermission
{
    public function __construct(
        private readonly VaultRbac $vault,
        private readonly ConfigRepository $config,
    ) {}

    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $ability = $abilities[0] ?? '';
        if ($ability === '') {
            abort((int) $this->config->get('vaultrbac.middleware.unauthorized_status', 403));
        }

        if (! $this->vault->check($ability)) {
            abort((int) $this->config->get('vaultrbac.middleware.missing_permission_status', 403));
        }

        return $next($request);
    }
}

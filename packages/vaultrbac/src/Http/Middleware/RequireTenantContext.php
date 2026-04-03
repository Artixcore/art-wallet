<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Contracts\TenantResolver;
use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aborts when no tenant can be resolved from the configured sources.
 * Does not fall back to default_tenant_id (explicit tenant for the request).
 */
final class RequireTenantContext
{
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly ConfigRepository $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->tenantResolver->resolve() === null) {
            abort((int) $this->config->get('vaultrbac.middleware.missing_tenant_status', 403));
        }

        return $next($request);
    }
}

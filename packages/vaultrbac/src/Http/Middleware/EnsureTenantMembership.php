<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Contracts\TenantMembershipVerifier;
use Artwallet\VaultRbac\Contracts\TenantResolver;
use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user is a member of the resolved tenant.
 */
final class EnsureTenantMembership
{
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly TenantMembershipVerifier $membershipVerifier,
        private readonly ConfigRepository $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->tenantResolver->resolve();
        if ($tenantId === null) {
            abort((int) $this->config->get('vaultrbac.middleware.missing_tenant_status', 403));
        }

        $user = $request->user();
        if (! $user instanceof Model) {
            abort((int) $this->config->get('vaultrbac.middleware.unauthorized_status', 403));
        }

        if (! $this->membershipVerifier->verify($user, $tenantId)) {
            abort((int) $this->config->get('vaultrbac.middleware.forbidden_tenant_status', 403));
        }

        return $next($request);
    }
}

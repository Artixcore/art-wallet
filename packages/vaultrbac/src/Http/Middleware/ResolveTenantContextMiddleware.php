<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Contracts\TenantMembershipVerifier;
use Artwallet\VaultRbac\Contracts\TenantRepository;
use Artwallet\VaultRbac\Http\IntegrationAuthorization;
use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Puts the tenant id from a route parameter onto the request attributes for tenant.sources request_attribute.
 * Parameter: route parameter name (e.g. organization).
 */
final class ResolveTenantContextMiddleware
{
    public function __construct(
        private readonly IntegrationAuthorization $integration,
        private readonly ConfigRepository $config,
        private readonly TenantRepository $tenants,
        private readonly TenantMembershipVerifier $membershipVerifier,
    ) {}

    public function handle(Request $request, Closure $next, string $routeParameter = ''): Response
    {
        $name = trim($routeParameter);
        $this->integration->abortIfInvalidArgument($name !== '', 'vrb.tenant.resolve requires a route parameter name.');

        $resolved = $request->route($name);
        $tenantId = $this->normalizeRouteValue($resolved);
        $this->integration->abortIfInvalidArgument(
            $tenantId !== null && $tenantId !== '',
            'Tenant route value is missing or invalid.',
        );

        if ((bool) $this->config->get('vaultrbac.integration.verify_tenant_exists_on_resolve', false)) {
            try {
                if (! $this->tenants->existsById($tenantId)) {
                    abort((int) $this->config->get('vaultrbac.middleware.forbidden_tenant_status', 403));
                }
            } catch (Throwable $e) {
                $this->integration->abortIntegrationFailure($e);
            }
        }

        $attribute = (string) $this->config->get('vaultrbac.integration.tenant_request_attribute', 'vaultrbac.tenant_id');
        $request->attributes->set($attribute, $tenantId);

        if ((bool) $this->config->get('vaultrbac.integration.verify_membership_after_tenant_resolve', false)) {
            $user = $request->user();
            if (! $user instanceof Model) {
                abort((int) $this->config->get('vaultrbac.middleware.unauthorized_status', 403));
            }
            try {
                if (! $this->membershipVerifier->verify($user, $tenantId)) {
                    abort((int) $this->config->get('vaultrbac.middleware.forbidden_tenant_status', 403));
                }
            } catch (Throwable $e) {
                $this->integration->abortIntegrationFailure($e);
            }
        }

        return $next($request);
    }

    private function normalizeRouteValue(mixed $resolved): string|int|null
    {
        if ($resolved === null) {
            return null;
        }

        if (is_object($resolved) && method_exists($resolved, 'getKey')) {
            $key = $resolved->getKey();

            return is_string($key) || is_int($key) ? $key : null;
        }

        if (is_int($resolved)) {
            return $resolved;
        }

        if (is_string($resolved)) {
            $t = trim($resolved);

            return $t === '' ? null : $t;
        }

        return null;
    }
}

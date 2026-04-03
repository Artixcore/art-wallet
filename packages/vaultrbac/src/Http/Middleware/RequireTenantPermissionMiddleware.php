<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Contracts\AuthorizationContextFactory;
use Artwallet\VaultRbac\Contracts\PermissionResolverInterface;
use Artwallet\VaultRbac\Http\IntegrationAuthorization;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Middleware spec: tenantRouteParameter,permission (comma-separated), e.g. organization,posts.update
 */
final class RequireTenantPermissionMiddleware
{
    public function __construct(
        private readonly AuthorizationContextFactory $contextFactory,
        private readonly PermissionResolverInterface $resolver,
        private readonly IntegrationAuthorization $integration,
    ) {}

    public function handle(Request $request, Closure $next, string $spec = ''): Response
    {
        $this->integration->assertAuthenticatedOrAbort($request);

        $user = $request->user();
        if (! $user instanceof Model) {
            abort($this->integration->missingPermissionStatus());
        }

        $parts = array_values(array_filter(array_map(trim(...), explode(',', $spec))));
        $this->integration->abortIfInvalidArgument(
            count($parts) >= 2,
            'vrb.context.tenant.permission requires tenantRouteParameter,permission.',
        );

        $tenantParam = $parts[0];
        $permission = $parts[1];

        $tenantId = $this->routeValue($request, $tenantParam);
        $this->integration->abortIfInvalidArgument(
            $tenantId !== null && $tenantId !== '',
            'Tenant route value is missing or invalid.',
        );

        $context = $this->contextFactory->makeFor($user)->withTenant($tenantId);

        try {
            if (! $this->resolver->authorize($context, $permission)) {
                abort($this->integration->missingPermissionStatus());
            }
        } catch (Throwable $e) {
            $this->integration->abortIntegrationFailure($e);
        }

        return $next($request);
    }

    private function routeValue(Request $request, string $parameter): string|int|null
    {
        $resolved = $request->route($parameter);
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

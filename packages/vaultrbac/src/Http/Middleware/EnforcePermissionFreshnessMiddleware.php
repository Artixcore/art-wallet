<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\TenantResolver;
use Artwallet\VaultRbac\Http\IntegrationAuthorization;
use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Compares a client header to the server permission cache version (fail-closed on mismatch or errors).
 */
final class EnforcePermissionFreshnessMiddleware
{
    public function __construct(
        private readonly PermissionCacheVersionRepository $versions,
        private readonly TenantResolver $tenantResolver,
        private readonly IntegrationAuthorization $integration,
        private readonly ConfigRepository $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) $this->config->get('vaultrbac.freshness.enabled', false)) {
            return $next($request);
        }

        $this->integration->assertAuthenticatedOrAbort($request);

        $header = (string) $this->config->get('vaultrbac.freshness.header', 'X-VaultRbac-Permission-Version');
        $scope = (string) $this->config->get('vaultrbac.freshness.scope', 'tenant');

        $clientRaw = $request->headers->get($header, '');
        $clientVersion = is_numeric($clientRaw) ? (int) $clientRaw : null;
        $this->integration->abortIfInvalidArgument(
            $clientVersion !== null && $clientVersion >= 0,
            'Missing or invalid permission version header.',
        );

        $tenantId = $this->tenantResolver->resolve();
        $this->integration->abortIfInvalidArgument(
            $tenantId !== null,
            'Tenant context is required for permission freshness enforcement.',
        );

        try {
            $serverVersion = $this->versions->getVersion($tenantId, $scope);
        } catch (Throwable $e) {
            $this->integration->abortIntegrationFailure($e);
        }

        if ($clientVersion !== $serverVersion) {
            abort((int) $this->config->get('vaultrbac.freshness.mismatch_status', 403));
        }

        return $next($request);
    }
}

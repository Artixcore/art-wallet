<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Contracts\ApprovalRequestRepository;
use Artwallet\VaultRbac\Contracts\TenantResolver;
use Artwallet\VaultRbac\Enums\ApprovalStatus;
use Artwallet\VaultRbac\Http\IntegrationAuthorization;
use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Ensures an approved ApprovalRequest exists for the correlation id and (by default) the current user as subject.
 * Middleware parameter: optional route parameter name for correlation id (default from config).
 */
final class RequireApprovedPrivilegeMiddleware
{
    public function __construct(
        private readonly ApprovalRequestRepository $approvalRequests,
        private readonly TenantResolver $tenantResolver,
        private readonly IntegrationAuthorization $integration,
        private readonly ConfigRepository $config,
    ) {}

    public function handle(Request $request, Closure $next, string $correlationRouteParameter = ''): Response
    {
        $this->integration->assertAuthenticatedOrAbort($request);

        $user = $request->user();
        if (! $user instanceof Model) {
            abort($this->integration->missingPermissionStatus());
        }

        $param = $correlationRouteParameter !== ''
            ? trim($correlationRouteParameter)
            : (string) $this->config->get('vaultrbac.approval_middleware.correlation_route_parameter', 'correlation_id');

        $this->integration->abortIfInvalidArgument($param !== '', 'Correlation route parameter name is empty.');

        $raw = $request->route($param);
        $correlationId = is_string($raw) ? trim($raw) : (is_scalar($raw) ? trim((string) $raw) : '');
        $this->integration->abortIfInvalidArgument($correlationId !== '', 'Missing approval correlation id in route.');

        try {
            $approval = $this->approvalRequests->findByCorrelationId($correlationId);
        } catch (Throwable $e) {
            $this->integration->abortIntegrationFailure($e);
        }

        if ($approval === null || $approval->status !== ApprovalStatus::Approved) {
            abort($this->integration->missingPermissionStatus());
        }

        if ((bool) $this->config->get('vaultrbac.approval_middleware.require_subject_is_authenticated_user', true)) {
            $sameType = $approval->subject_type === $user->getMorphClass();
            $sameKey = (string) $approval->subject_id === (string) $user->getKey();
            if (! $sameType || ! $sameKey) {
                abort($this->integration->missingPermissionStatus());
            }
        }

        if ((bool) $this->config->get('vaultrbac.approval_middleware.tenant_must_match_context', true)) {
            $ctxTenant = $this->tenantResolver->resolve();
            if ($ctxTenant === null || (string) $ctxTenant !== (string) $approval->tenant_id) {
                abort($this->integration->missingPermissionStatus());
            }
        }

        return $next($request);
    }
}

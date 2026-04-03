<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Http\IntegrationAuthorization;
use Artwallet\VaultRbac\VaultRbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Requires a single permission (first middleware parameter). Additional parameters are ignored.
 */
class RequirePermissionMiddleware
{
    public function __construct(
        private readonly VaultRbac $vault,
        private readonly IntegrationAuthorization $integration,
    ) {}

    public function handle(Request $request, Closure $next, string $ability = ''): Response
    {
        $this->integration->assertAuthenticatedOrAbort($request);

        $trimmed = trim($ability);
        $this->integration->abortIfInvalidAbilities($trimmed !== '' ? [$trimmed] : []);

        try {
            if (! $this->vault->check($trimmed)) {
                abort($this->integration->missingPermissionStatus());
            }
        } catch (Throwable $e) {
            $this->integration->abortIntegrationFailure($e);
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Http\IntegrationAuthorization;
use Artwallet\VaultRbac\VaultRbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pipe-separated role names in one parameter (admin|editor), matching legacy vault.any-role.
 */
class RequireAnyRoleMiddleware
{
    public function __construct(
        private readonly VaultRbac $vault,
        private readonly IntegrationAuthorization $integration,
    ) {}

    public function handle(Request $request, Closure $next, string $roles = ''): Response
    {
        $this->integration->assertAuthenticatedOrAbort($request);

        $names = array_values(array_filter(array_map(trim(...), explode('|', $roles))));
        $this->integration->abortIfInvalidRoles($names);

        foreach ($names as $role) {
            if ($this->vault->hasRole($role)) {
                return $next($request);
            }
        }

        abort($this->integration->missingPermissionStatus());
    }
}

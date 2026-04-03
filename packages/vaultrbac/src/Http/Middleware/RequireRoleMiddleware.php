<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Http\IntegrationAuthorization;
use Artwallet\VaultRbac\VaultRbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRoleMiddleware
{
    public function __construct(
        private readonly VaultRbac $vault,
        private readonly IntegrationAuthorization $integration,
    ) {}

    public function handle(Request $request, Closure $next, string $role = ''): Response
    {
        $this->integration->assertAuthenticatedOrAbort($request);

        $name = trim($role);
        $this->integration->abortIfInvalidRoles($name !== '' ? [$name] : []);

        if (! $this->vault->hasRole($name)) {
            abort($this->integration->missingPermissionStatus());
        }

        return $next($request);
    }
}

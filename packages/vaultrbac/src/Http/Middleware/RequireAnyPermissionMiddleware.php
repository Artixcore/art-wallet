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
 * Comma-separated abilities in a single middleware parameter, e.g. vrb.permission.any:a,b,c
 */
final class RequireAnyPermissionMiddleware
{
    public function __construct(
        private readonly VaultRbac $vault,
        private readonly IntegrationAuthorization $integration,
    ) {}

    public function handle(Request $request, Closure $next, string $abilities = ''): Response
    {
        $this->integration->assertAuthenticatedOrAbort($request);

        $list = self::parseList($abilities);
        $this->integration->abortIfInvalidAbilities($list);

        try {
            if (! $this->vault->checkAny($list)) {
                abort($this->integration->missingPermissionStatus());
            }
        } catch (Throwable $e) {
            $this->integration->abortIntegrationFailure($e);
        }

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private static function parseList(string $raw): array
    {
        $parts = array_map(trim(...), explode(',', $raw));

        return array_values(array_filter($parts, static fn (string $p): bool => $p !== ''));
    }
}

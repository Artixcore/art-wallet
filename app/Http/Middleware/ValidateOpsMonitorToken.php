<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enables GET /ops/monitor/health only when OPS_MONITOR_TOKEN is set; compares via hash_equals.
 */
final class ValidateOpsMonitorToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('observability.monitoring.token');
        if (! is_string($token) || $token === '') {
            abort(404);
        }

        $bearer = $request->bearerToken();
        $query = $request->query('token');
        $provided = is_string($bearer) && $bearer !== '' ? $bearer : (is_string($query) ? $query : null);

        if ($provided === null || $provided === '' || ! hash_equals($token, $provided)) {
            abort(401);
        }

        return $next($request);
    }
}

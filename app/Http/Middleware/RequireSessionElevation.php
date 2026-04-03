<?php

namespace App\Http\Middleware;

use App\Services\DeviceTrustService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires artwallet.session_elevated_at (trusted-device approval or equivalent).
 */
class RequireSessionElevation
{
    public function __construct(
        private DeviceTrustService $deviceTrust,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->deviceTrust->isSessionElevated($request->session())) {
            return response()->json(['ok' => false, 'error' => 'session_not_elevated'], 403);
        }

        return $next($request);
    }
}

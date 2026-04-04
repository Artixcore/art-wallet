<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SanctumPersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the access token is bound to an API device, require a matching X-ArtWallet-Device-Id header.
 */
final class EnsureApiDeviceBinding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $token = $user->currentAccessToken();
        if (! $token instanceof SanctumPersonalAccessToken || $token->api_device_id === null) {
            return $next($request);
        }

        $header = $request->header('X-ArtWallet-Device-Id');
        if (! is_string($header) || $header === '') {
            abort(401, __('Device identifier required for this token.'));
        }

        $device = $token->apiDevice;
        if ($device === null || $device->device_id !== $header) {
            abort(403, __('Device binding mismatch.'));
        }

        return $next($request);
    }
}

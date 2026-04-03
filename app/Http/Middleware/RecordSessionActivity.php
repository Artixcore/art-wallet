<?php

namespace App\Http\Middleware;

use App\Models\UserSessionRecord;
use App\Services\SessionFingerprintService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordSessionActivity
{
    public function __construct(
        private SessionFingerprintService $fingerprints,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()) {
            $hash = $this->fingerprints->hashSessionId($request->session()->getId());
            UserSessionRecord::query()
                ->where('user_id', $request->user()->id)
                ->where('session_id_hash', $hash)
                ->whereNull('revoked_at')
                ->update(['last_seen_at' => now()]);
        }

        return $response;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSessionRecord;
use App\Services\SessionFingerprintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SessionSecurityAjaxController extends Controller
{
    public function index(Request $request, SessionFingerprintService $fingerprints): JsonResponse
    {
        $this->authorize('viewAny', UserSessionRecord::class);

        $currentHash = $fingerprints->hashSessionId($request->session()->getId());

        $sessions = UserSessionRecord::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('revoked_at')
            ->orderByDesc('last_seen_at')
            ->limit(50)
            ->get()
            ->map(function (UserSessionRecord $r) use ($currentHash) {
                return [
                    'id' => $r->id,
                    'is_current' => hash_equals($r->session_id_hash, $currentHash),
                    'ip_hash_prefix' => $r->ip_hash ? substr($r->ip_hash, 0, 12).'…' : null,
                    'user_agent_hash_prefix' => $r->user_agent_hash ? substr($r->user_agent_hash, 0, 12).'…' : null,
                    'created_at' => $r->created_at?->toIso8601String(),
                    'last_seen_at' => $r->last_seen_at?->toIso8601String(),
                ];
            });

        return response()->json(['ok' => true, 'sessions' => $sessions]);
    }

    public function revoke(Request $request, UserSessionRecord $userSessionRecord, SessionFingerprintService $fingerprints): JsonResponse
    {
        $this->authorize('revoke', $userSessionRecord);

        if ($userSessionRecord->revoked_at !== null) {
            return response()->json(['ok' => false, 'error' => 'already_revoked'], 422);
        }

        $currentHash = $fingerprints->hashSessionId($request->session()->getId());
        if (hash_equals($userSessionRecord->session_id_hash, $currentHash)) {
            return response()->json(['ok' => false, 'error' => 'cannot_revoke_current'], 422);
        }

        $this->destroyLaravelSession($userSessionRecord);

        $userSessionRecord->revoked_at = now();
        $userSessionRecord->save();

        return response()->json(['ok' => true]);
    }

    public function revokeOthers(Request $request, SessionFingerprintService $fingerprints): JsonResponse
    {
        $this->authorize('viewAny', UserSessionRecord::class);

        $currentHash = $fingerprints->hashSessionId($request->session()->getId());

        $records = UserSessionRecord::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('revoked_at')
            ->where('session_id_hash', '!=', $currentHash)
            ->get();

        DB::transaction(function () use ($records): void {
            foreach ($records as $record) {
                $this->destroyLaravelSession($record);
                $record->revoked_at = now();
                $record->save();
            }
        });

        return response()->json(['ok' => true, 'revoked_count' => $records->count()]);
    }

    private function destroyLaravelSession(UserSessionRecord $record): void
    {
        $enc = $record->session_id_encrypted;
        if ($enc === null || $enc === '') {
            return;
        }

        try {
            $sessionId = Crypt::decryptString($enc);
        } catch (\Throwable) {
            return;
        }

        DB::table('sessions')->where('id', $sessionId)->delete();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\ApproveDeviceChallengeRequest;
use App\Models\DeviceChallenge;
use App\Models\LoginTrustedDevice;
use App\Services\DeviceTrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceChallengeAjaxController extends Controller
{
    public function store(Request $request, DeviceTrustService $trust): JsonResponse
    {
        $request->validate([
            'purpose' => ['required', 'string', Rule::in([
                DeviceTrustService::PURPOSE_NEW_DEVICE,
                DeviceTrustService::PURPOSE_STEP_UP,
                DeviceTrustService::PURPOSE_RECOVERY,
            ])],
        ]);

        $challenge = $trust->issueChallenge(
            $request->user(),
            $request->session()->getId(),
            $request->input('purpose'),
        );

        return response()->json([
            'ok' => true,
            'challenge' => [
                'public_uuid' => $challenge->public_uuid,
                'nonce' => $challenge->challenge_nonce,
                'client_code' => $challenge->client_code,
                'expires_at' => $challenge->expires_at->toIso8601String(),
                'purpose' => $challenge->purpose,
            ],
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $rows = DeviceChallenge::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (DeviceChallenge $c) => [
                'public_uuid' => $c->public_uuid,
                'nonce' => $c->challenge_nonce,
                'client_code' => $c->client_code,
                'purpose' => $c->purpose,
                'expires_at' => $c->expires_at->toIso8601String(),
            ]);

        return response()->json(['ok' => true, 'pending' => $rows]);
    }

    public function approve(ApproveDeviceChallengeRequest $request, DeviceTrustService $trust): JsonResponse
    {
        $device = LoginTrustedDevice::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($request->validated('login_trusted_device_id'))
            ->first();

        if ($device === null) {
            return response()->json(['ok' => false, 'error' => 'invalid_request'], 403);
        }

        $result = $trust->approveChallenge(
            $request->user(),
            $request->validated('challenge_public_uuid'),
            $device,
            $request->validated('signature'),
        );

        if (! $result['ok']) {
            return response()->json(['ok' => false, 'error' => 'verification_failed'], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function status(Request $request, DeviceTrustService $trust): JsonResponse
    {
        $status = $trust->challengeStatusForCurrentSession($request->user(), $request->session()->getId());
        if ($status === null) {
            return response()->json(['ok' => true, 'challenge' => null]);
        }

        if (($status['status'] ?? '') === 'approved') {
            $trust->tryElevateCurrentSession($request->user(), $request->session());
        }

        return response()->json(['ok' => true, 'challenge' => $status]);
    }
}

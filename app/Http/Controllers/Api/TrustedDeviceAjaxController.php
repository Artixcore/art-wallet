<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreTrustedDeviceRequest;
use App\Models\LoginTrustedDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrustedDeviceAjaxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LoginTrustedDevice::class);

        $devices = LoginTrustedDevice::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('trusted_at')
            ->get()
            ->map(fn (LoginTrustedDevice $d) => [
                'id' => $d->id,
                'public_key' => $d->public_key,
                'key_alg' => $d->key_alg,
                'trust_version' => $d->trust_version,
                'device_label_ciphertext' => $d->device_label_ciphertext,
                'trusted_at' => $d->trusted_at?->toIso8601String(),
                'last_used_at' => $d->last_used_at?->toIso8601String(),
                'revoked_at' => $d->revoked_at?->toIso8601String(),
            ]);

        return response()->json(['ok' => true, 'devices' => $devices]);
    }

    public function store(StoreTrustedDeviceRequest $request): JsonResponse
    {
        $this->authorize('create', LoginTrustedDevice::class);

        $pk = $request->validated('public_key');
        $exists = LoginTrustedDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('public_key', $pk)
            ->whereNull('revoked_at')
            ->exists();

        if ($exists) {
            return response()->json(['ok' => false, 'error' => 'device_already_registered'], 422);
        }

        LoginTrustedDevice::query()->create([
            'user_id' => $request->user()->id,
            'public_key' => $pk,
            'key_alg' => 'ed25519',
            'trust_version' => 1,
            'device_label_ciphertext' => $request->validated('device_label_ciphertext'),
            'fingerprint_signals_json' => $request->validated('fingerprint_signals_json'),
            'trusted_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, LoginTrustedDevice $login_trusted_device): JsonResponse
    {
        $this->authorize('delete', $login_trusted_device);

        if ($login_trusted_device->isRevoked()) {
            return response()->json(['ok' => false, 'error' => 'already_revoked'], 422);
        }

        $login_trusted_device->revoked_at = now();
        $login_trusted_device->trust_version = $login_trusted_device->trust_version + 1;
        $login_trusted_device->save();

        return response()->json(['ok' => true]);
    }
}

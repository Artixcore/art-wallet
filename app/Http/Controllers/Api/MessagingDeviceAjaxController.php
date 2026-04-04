<?php

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreMessagingDeviceRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Models\MessagingDevice;
use Illuminate\Http\JsonResponse;

class MessagingDeviceAjaxController extends Controller
{
    public function store(StoreMessagingDeviceRequest $request): JsonResponse
    {
        $x = $request->input('device_x25519_public_key_b64');
        $raw = base64_decode((string) $x, true);
        if ($raw === false || strlen($raw) !== 32) {
            return AjaxEnvelope::error(
                AjaxResponseCode::CryptoEnvelopeInvalid,
                __('Invalid device X25519 public key.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(422);
        }

        $ed = $request->input('device_ed25519_public_key_b64');
        if ($ed !== null && $ed !== '') {
            $rawEd = base64_decode((string) $ed, true);
            if ($rawEd === false || strlen($rawEd) !== 32) {
                return AjaxEnvelope::error(
                    AjaxResponseCode::CryptoEnvelopeInvalid,
                    __('Invalid device Ed25519 public key.'),
                    NotificationSeverity::Danger,
                )->toJsonResponse(422);
            }
        }

        $device = MessagingDevice::query()->updateOrCreate(
            [
                'user_id' => (int) $request->user()->id,
                'device_id' => $request->input('device_id'),
            ],
            [
                'device_ed25519_public_key_b64' => $ed,
                'device_x25519_public_key_b64' => $x,
                'revoked_at' => null,
            ],
        );

        return AjaxEnvelope::ok(
            __('Messaging device registered.'),
            data: [
                'device_id' => $device->device_id,
                'id' => $device->id,
            ],
            meta: ['retryable' => false],
        )->toJsonResponse();
    }
}

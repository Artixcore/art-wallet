<?php

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\UpdateMessagingIdentityRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use Illuminate\Http\JsonResponse;

class MessagingIdentityAjaxController extends Controller
{
    public function update(UpdateMessagingIdentityRequest $request): JsonResponse
    {
        $b64 = $request->input('messaging_x25519_public_key');
        $raw = base64_decode((string) $b64, true);
        if ($raw === false || strlen($raw) !== 32) {
            return AjaxEnvelope::error(
                AjaxResponseCode::CryptoEnvelopeInvalid,
                __('Invalid public key encoding.'),
                NotificationSeverity::Danger,
                meta: [
                    'retryable' => false,
                    'client_behavior' => 'rekey',
                ],
            )->toJsonResponse(422);
        }

        $request->user()->forceFill([
            'messaging_x25519_public_key' => $b64,
        ])->save();

        return AjaxEnvelope::ok(
            __('Messaging identity key updated.'),
            data: ['registered' => true],
            meta: [
                'retryable' => false,
                'conversation_state' => [],
            ],
        )->toJsonResponse();
    }
}

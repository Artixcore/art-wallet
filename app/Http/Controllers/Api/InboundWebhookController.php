<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Webhooks\Services\OutboundWebhookSigner;
use App\Http\Controllers\Controller;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Models\IntegrationEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Validates inbound webhook-style callbacks using the same HMAC contract as outbound deliveries.
 */
final class InboundWebhookController extends Controller
{
    public function verify(Request $request, OutboundWebhookSigner $signer, IntegrationEndpoint $integrationEndpoint): JsonResponse
    {
        $payload = $request->all();
        $sig = (string) $request->header('X-ArtWallet-Signature', '');
        $ts = (string) $request->header('X-ArtWallet-Timestamp', '');

        $plain = $integrationEndpoint->secret_cipher;
        if (! is_string($plain) || $plain === '') {
            return AjaxEnvelope::error(
                AjaxResponseCode::InvalidRequest,
                __('Integration is not configured for signed callbacks.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(422);
        }

        if (! $signer->verify($plain, $ts, $sig, is_array($payload) ? $payload : [])) {
            return AjaxEnvelope::error(
                AjaxResponseCode::InvalidRequest,
                __('Invalid webhook signature or timestamp.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(401);
        }

        return AjaxEnvelope::ok(
            message: __('Verified.'),
            data: ['verified' => true],
            severity: NotificationSeverity::Success,
        )->toJsonResponse(200);
    }
}

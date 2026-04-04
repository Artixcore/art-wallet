<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\ApiTokens\Exceptions\RefreshTokenReuseException;
use App\Domain\ApiTokens\Services\ApiTokenIssuer;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApiLoginRequest;
use App\Http\Requests\Api\V1\ApiRefreshRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function login(ApiLoginRequest $request, ApiTokenIssuer $issuer): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        try {
            if ($user === null) {
                throw new \InvalidArgumentException(__('Invalid credentials.'));
            }

            $issued = $issuer->issueWithPassword(
                $user,
                $request->validated('password'),
                $request->validated('device_id'),
                $request->validated('device_name'),
                $request->validated('platform'),
            );
        } catch (\InvalidArgumentException $e) {
            return AjaxEnvelope::error(
                AjaxResponseCode::Unauthorized,
                __('Invalid credentials.'),
                NotificationSeverity::Danger,
                meta: ['requires_reauth' => true],
            )->toJsonResponse(401);
        }

        return AjaxEnvelope::ok(
            message: __('Authenticated.'),
            data: [
                'access_token' => $issued['access_token'],
                'refresh_token' => $issued['refresh_token'],
                'token_type' => $issued['token_type'],
                'expires_in' => $issued['expires_in'],
                'device_id' => $issued['device']->device_id,
            ],
            severity: NotificationSeverity::Success,
            meta: [
                'requires_reauth' => false,
                'retryable' => false,
            ],
        )->toJsonResponse(200);
    }

    public function refresh(ApiRefreshRequest $request, ApiTokenIssuer $issuer): JsonResponse
    {
        try {
            $issued = $issuer->rotateWithRefreshToken(
                $request->validated('refresh_token'),
                $request->validated('device_id'),
            );
        } catch (RefreshTokenReuseException) {
            return AjaxEnvelope::error(
                AjaxResponseCode::TokenReuseDetected,
                __('Refresh token reuse detected. All sessions for this device family were revoked.'),
                NotificationSeverity::Danger,
                meta: ['requires_reauth' => true, 'retryable' => false],
            )->toJsonResponse(401);
        } catch (\InvalidArgumentException $e) {
            return AjaxEnvelope::error(
                AjaxResponseCode::Unauthorized,
                $e->getMessage(),
                NotificationSeverity::Danger,
                meta: ['requires_reauth' => true],
            )->toJsonResponse(401);
        }

        return AjaxEnvelope::ok(
            message: __('Token refreshed.'),
            data: [
                'access_token' => $issued['access_token'],
                'refresh_token' => $issued['refresh_token'],
                'token_type' => $issued['token_type'],
                'expires_in' => $issued['expires_in'],
                'device_id' => $issued['device']->device_id,
            ],
            meta: ['requires_reauth' => false],
        )->toJsonResponse(200);
    }

    public function logout(Request $request, ApiTokenIssuer $issuer): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return AjaxEnvelope::error(
                AjaxResponseCode::Unauthorized,
                __('Unauthenticated.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(401);
        }

        $refresh = $request->input('refresh_token');
        if (is_string($refresh) && $refresh !== '') {
            $issuer->revokeRefreshFamily($refresh);
        }

        $user->currentAccessToken()?->delete();

        return AjaxEnvelope::ok(
            message: __('Logged out.'),
            data: [],
        )->toJsonResponse(200);
    }
}

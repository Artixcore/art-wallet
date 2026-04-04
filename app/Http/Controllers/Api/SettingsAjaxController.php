<?php

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Settings\Services\SettingsCenterApplicationService;
use App\Domain\Settings\Services\SettingsResolver;
use App\Domain\Settings\Services\StepUpTokenService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\SettingsStepUpRequest;
use App\Http\Requests\Ajax\UpdateMessagingPrivacySettingsRequest;
use App\Http\Requests\Ajax\UpdateRiskThresholdSettingsRequest;
use App\Http\Requests\Ajax\UpdateSecurityPolicySettingsRequest;
use App\Http\Requests\Ajax\UpdateUserSettingsRequest;
use App\Http\Responses\AjaxEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsAjaxController extends Controller
{
    public function __construct(
        private readonly SettingsResolver $resolver,
        private readonly SettingsCenterApplicationService $settings,
        private readonly StepUpTokenService $stepUp,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $data = $this->resolver->resolveUserSnapshot($request->user());

        return AjaxEnvelope::ok(
            message: '',
            data: $data,
        )->toJsonResponse();
    }

    public function updateUser(UpdateUserSettingsRequest $request): JsonResponse
    {
        $snapshot = $this->settings->updateUserSettings($request->user(), $request->validated(), $request);

        return AjaxEnvelope::ok(
            message: __('Preferences saved.'),
            data: $snapshot,
        )->toJsonResponse();
    }

    public function updateSecurityPolicy(UpdateSecurityPolicySettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $stepUpToken = $validated['step_up_token'] ?? null;
        unset($validated['step_up_token']);

        $snapshot = $this->settings->updateSecurityPolicy(
            $request->user(),
            $validated,
            $stepUpToken,
            $request,
        );

        return AjaxEnvelope::ok(
            message: __('Security policy updated.'),
            data: $snapshot,
        )->toJsonResponse();
    }

    public function updateMessagingPrivacy(UpdateMessagingPrivacySettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $stepUpToken = $validated['step_up_token'] ?? null;
        unset($validated['step_up_token']);

        $snapshot = $this->settings->updateMessagingPrivacy(
            $request->user(),
            $validated,
            $stepUpToken,
            $request,
        );

        return AjaxEnvelope::ok(
            message: __('Messaging privacy settings saved.'),
            data: $snapshot,
        )->toJsonResponse();
    }

    public function updateRiskThresholds(UpdateRiskThresholdSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $stepUpToken = $validated['step_up_token'] ?? null;
        unset($validated['step_up_token']);

        $snapshot = $this->settings->updateRiskThresholds(
            $request->user(),
            $validated,
            $stepUpToken,
            $request,
        );

        return AjaxEnvelope::ok(
            message: __('Risk alert settings saved.'),
            data: $snapshot,
        )->toJsonResponse();
    }

    public function stepUp(SettingsStepUpRequest $request): JsonResponse
    {
        $token = $this->stepUp->issueToken($request->user());

        return AjaxEnvelope::ok(
            message: __('Password verified. You can save sensitive changes.'),
            data: ['step_up_token' => $token],
            severity: NotificationSeverity::Success,
        )->toJsonResponse();
    }
}

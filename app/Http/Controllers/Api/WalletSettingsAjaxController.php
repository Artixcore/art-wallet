<?php

namespace App\Http\Controllers\Api;

use App\Domain\Settings\Services\SettingsCenterApplicationService;
use App\Domain\Settings\Services\SettingsResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\UpdateWalletSettingsRequest;
use App\Http\Requests\Ajax\UpdateWalletTransactionPolicyRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletSettingsAjaxController extends Controller
{
    public function __construct(
        private readonly SettingsResolver $resolver,
        private readonly SettingsCenterApplicationService $settings,
    ) {}

    public function show(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);

        $data = $this->resolver->resolveWalletSnapshot($request->user(), $wallet);

        return AjaxEnvelope::ok(
            message: '',
            data: $data,
        )->toJsonResponse();
    }

    public function updateWallet(UpdateWalletSettingsRequest $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);

        $snapshot = $this->settings->updateWalletSettings(
            $request->user(),
            $wallet,
            $request->validated(),
            $request,
        );

        return AjaxEnvelope::ok(
            message: __('Wallet preferences saved.'),
            data: $snapshot,
        )->toJsonResponse();
    }

    public function updateTransactionPolicy(UpdateWalletTransactionPolicyRequest $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);

        $validated = $request->validated();
        $stepUpToken = $validated['step_up_token'] ?? null;
        unset($validated['step_up_token']);

        $snapshot = $this->settings->updateWalletTransactionPolicy(
            $request->user(),
            $wallet,
            $validated,
            $stepUpToken,
            $request,
        );

        return AjaxEnvelope::ok(
            message: __('Transaction policy saved.'),
            data: $snapshot,
        )->toJsonResponse();
    }
}

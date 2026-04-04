<?php

namespace App\Domain\Settings\Services;

use App\Domain\Settings\Exceptions\SettingsConflictException;
use App\Models\MessagingPrivacySetting;
use App\Models\RiskThresholdSetting;
use App\Models\User;
use App\Models\UserSecurityPolicy;
use App\Models\UserSetting;
use App\Models\Wallet;
use App\Models\WalletSetting;
use App\Models\WalletTransactionPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SettingsCenterApplicationService
{
    public function __construct(
        private readonly SettingsResolver $resolver,
        private readonly SettingsAuditLogger $audit,
        private readonly StepUpTokenService $stepUp,
        private readonly SettingsRiskEvaluator $risk,
        private readonly SettingsNotificationService $notify,
    ) {}

    /**
     * @param  array{locale?: string|null, timezone?: string|null, theme?: string, ui_preferences?: array<string, mixed>, ui_preferences_version?: int, settings_version: int}  $data
     * @return array<string, mixed>
     */
    public function updateUserSettings(User $user, array $data, Request $request): array
    {
        return DB::transaction(function () use ($user, $data, $request): array {
            $this->resolver->resolveUserSnapshot($user);
            $row = UserSetting::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ((int) $row->settings_version !== (int) $data['settings_version']) {
                throw new SettingsConflictException;
            }

            $old = [
                'locale' => $row->locale,
                'timezone' => $row->timezone,
                'theme' => $row->theme,
                'ui_preferences' => $row->ui_preferences_json,
            ];

            $row->locale = $data['locale'] ?? null;
            $row->timezone = $data['timezone'] ?? null;
            $row->theme = $data['theme'] ?? $row->theme;
            if (isset($data['ui_preferences'])) {
                $row->ui_preferences_json = $data['ui_preferences'];
            }
            if (isset($data['ui_preferences_version'])) {
                $row->ui_preferences_version = (int) $data['ui_preferences_version'];
            }
            $row->settings_version = $row->settings_version + 1;
            $row->save();

            foreach (['locale', 'timezone', 'theme'] as $k) {
                $ov = $old[$k] ?? null;
                $nv = $row->{$k} ?? null;
                if ($ov !== $nv) {
                    $this->audit->logChange($user, 'user', null, $k, $ov, $nv, $request);
                }
            }
            if (isset($data['ui_preferences'])) {
                $this->audit->logChange($user, 'user', null, 'ui_preferences', $old['ui_preferences'] ?? null, $row->ui_preferences_json, $request);
            }

            $this->audit->logAuditAction($user, 'settings.user.updated', $request, ['settings_version' => $row->settings_version]);

            return $this->resolver->resolveUserSnapshot($user);
        });
    }

    /**
     * @param  array{idle_timeout_minutes: int, max_session_duration_minutes?: int|null, notify_new_device_login: bool, settings_version: int}  $data
     * @return array<string, mixed>
     */
    public function updateSecurityPolicy(User $user, array $data, ?string $stepUpToken, Request $request): array
    {
        return DB::transaction(function () use ($user, $data, $stepUpToken, $request): array {
            $this->resolver->resolveUserSnapshot($user);
            $row = UserSecurityPolicy::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ((int) $row->settings_version !== (int) $data['settings_version']) {
                throw new SettingsConflictException;
            }

            $needsStepUp = $this->risk->securityPolicyNeedsStepUp(
                $row,
                (int) $data['idle_timeout_minutes'],
                array_key_exists('max_session_duration_minutes', $data) ? $data['max_session_duration_minutes'] : $row->max_session_duration_minutes,
                (bool) $data['notify_new_device_login'],
            );

            if ($needsStepUp) {
                $this->stepUp->assertValidAndConsume($user, $stepUpToken);
            }

            $oldIdle = $row->idle_timeout_minutes;
            $oldMax = $row->max_session_duration_minutes;
            $oldNotify = $row->notify_new_device_login;

            $row->idle_timeout_minutes = (int) $data['idle_timeout_minutes'];
            if (array_key_exists('max_session_duration_minutes', $data)) {
                $row->max_session_duration_minutes = $data['max_session_duration_minutes'];
            }
            $row->notify_new_device_login = (bool) $data['notify_new_device_login'];
            $row->settings_version = $row->settings_version + 1;
            $row->save();

            $this->audit->logChange($user, 'security', null, 'idle_timeout_minutes', $oldIdle, $row->idle_timeout_minutes, $request);
            $this->audit->logChange($user, 'security', null, 'max_session_duration_minutes', $oldMax, $row->max_session_duration_minutes, $request);
            $this->audit->logChange($user, 'security', null, 'notify_new_device_login', $oldNotify, $row->notify_new_device_login, $request);
            $this->audit->logAuditAction($user, 'settings.security_policy.updated', $request, ['settings_version' => $row->settings_version]);

            if ($needsStepUp) {
                $this->notify->notifySecurityPolicyRelaxed($user);
            }

            return $this->resolver->resolveUserSnapshot($user);
        });
    }

    /**
     * @param  array{read_receipts_enabled: bool, typing_indicators_enabled: bool, max_attachment_mb: int, safety_warnings_enabled: bool, discoverable_by_sol_address: string, require_dm_approval: bool, hide_profile_until_dm_accepted: bool, settings_version: int}  $data
     * @return array<string, mixed>
     */
    public function updateMessagingPrivacy(User $user, array $data, ?string $stepUpToken, Request $request): array
    {
        return DB::transaction(function () use ($user, $data, $stepUpToken, $request): array {
            $this->resolver->resolveUserSnapshot($user);
            $row = MessagingPrivacySetting::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ((int) $row->settings_version !== (int) $data['settings_version']) {
                throw new SettingsConflictException;
            }

            $needsStepUpSafety = $this->risk->messagingPrivacyNeedsStepUp(
                (bool) $row->safety_warnings_enabled,
                (bool) $data['safety_warnings_enabled'],
            );

            $needsStepUpDiscoverability = $this->risk->messagingDiscoverabilityNeedsStepUp(
                (string) $row->discoverable_by_sol_address,
                (string) $data['discoverable_by_sol_address'],
                (bool) $row->require_dm_approval,
                (bool) $data['require_dm_approval'],
                (bool) $row->hide_profile_until_dm_accepted,
                (bool) $data['hide_profile_until_dm_accepted'],
            );

            if ($needsStepUpSafety || $needsStepUpDiscoverability) {
                $this->stepUp->assertValidAndConsume($user, $stepUpToken);
            }

            $oldSafety = $row->safety_warnings_enabled;
            $row->read_receipts_enabled = (bool) $data['read_receipts_enabled'];
            $row->typing_indicators_enabled = (bool) $data['typing_indicators_enabled'];
            $row->max_attachment_mb = (int) $data['max_attachment_mb'];
            $row->safety_warnings_enabled = (bool) $data['safety_warnings_enabled'];
            $row->discoverable_by_sol_address = (string) $data['discoverable_by_sol_address'];
            $row->require_dm_approval = (bool) $data['require_dm_approval'];
            $row->hide_profile_until_dm_accepted = (bool) $data['hide_profile_until_dm_accepted'];
            $row->settings_version = $row->settings_version + 1;
            $row->save();

            $this->audit->logChange($user, 'messaging', null, 'safety_warnings_enabled', $oldSafety, $row->safety_warnings_enabled, $request);
            $this->audit->logAuditAction($user, 'settings.messaging_privacy.updated', $request, ['settings_version' => $row->settings_version]);

            if ($needsStepUpSafety) {
                $this->notify->notifyMessagingPrivacyWeakened($user);
            }

            return $this->resolver->resolveUserSnapshot($user);
        });
    }

    /**
     * @param  array{large_tx_alert_fiat?: string|null, large_tx_alert_currency: string, settings_version: int}  $data
     * @return array<string, mixed>
     */
    public function updateRiskThresholds(User $user, array $data, ?string $stepUpToken, Request $request): array
    {
        return DB::transaction(function () use ($user, $data, $stepUpToken, $request): array {
            $this->resolver->resolveUserSnapshot($user);
            $row = RiskThresholdSetting::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ((int) $row->settings_version !== (int) $data['settings_version']) {
                throw new SettingsConflictException;
            }

            $oldFiat = $row->large_tx_alert_fiat !== null ? (string) $row->large_tx_alert_fiat : null;
            $newFiat = $data['large_tx_alert_fiat'] ?? null;
            $newFiatStr = $newFiat !== null && $newFiat !== '' ? $newFiat : null;

            $needsStepUp = $this->risk->riskThresholdNeedsStepUp($oldFiat, $newFiatStr);
            if ($needsStepUp) {
                $this->stepUp->assertValidAndConsume($user, $stepUpToken);
            }

            $row->large_tx_alert_fiat = $newFiatStr;
            $row->large_tx_alert_currency = $data['large_tx_alert_currency'];
            $row->settings_version = $row->settings_version + 1;
            $row->save();

            $this->audit->logChange($user, 'risk', null, 'large_tx_alert_fiat', $oldFiat, $newFiatStr, $request);
            $this->audit->logAuditAction($user, 'settings.risk_thresholds.updated', $request, ['settings_version' => $row->settings_version]);

            if ($needsStepUp) {
                $this->notify->notifyRiskThresholdRaised($user);
            }

            return $this->resolver->resolveUserSnapshot($user);
        });
    }

    /**
     * @param  array{default_fee_tier?: string|null, show_testnet_assets: bool, settings_version: int}  $data
     * @return array<string, mixed>
     */
    public function updateWalletSettings(User $user, Wallet $wallet, array $data, Request $request): array
    {
        if ((int) $wallet->user_id !== (int) $user->id) {
            abort(404);
        }

        return DB::transaction(function () use ($user, $wallet, $data, $request): array {
            $this->resolver->resolveWalletSnapshot($user, $wallet);
            $row = WalletSetting::query()->where('wallet_id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ((int) $row->settings_version !== (int) $data['settings_version']) {
                throw new SettingsConflictException;
            }

            $oldFee = $row->default_fee_tier;
            $oldTest = $row->show_testnet_assets;

            $row->default_fee_tier = $data['default_fee_tier'] ?? null;
            $row->show_testnet_assets = (bool) $data['show_testnet_assets'];
            $row->settings_version = $row->settings_version + 1;
            $row->save();

            $this->audit->logChange($user, 'wallet', $wallet->id, 'default_fee_tier', $oldFee, $row->default_fee_tier, $request);
            $this->audit->logChange($user, 'wallet', $wallet->id, 'show_testnet_assets', $oldTest, $row->show_testnet_assets, $request);
            $this->audit->logAuditAction($user, 'settings.wallet.updated', $request, ['wallet_id' => $wallet->id]);

            return $this->resolver->resolveWalletSnapshot($user, $wallet);
        });
    }

    /**
     * @param  array{confirm_above_amount?: string|null, fiat_currency: string, require_second_approval: bool, settings_version: int}  $data
     * @return array<string, mixed>
     */
    public function updateWalletTransactionPolicy(User $user, Wallet $wallet, array $data, ?string $stepUpToken, Request $request): array
    {
        if ((int) $wallet->user_id !== (int) $user->id) {
            abort(404);
        }

        return DB::transaction(function () use ($user, $wallet, $data, $stepUpToken, $request): array {
            $this->resolver->resolveWalletSnapshot($user, $wallet);
            $row = WalletTransactionPolicy::query()->where('wallet_id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ((int) $row->settings_version !== (int) $data['settings_version']) {
                throw new SettingsConflictException;
            }

            $newConfirm = $data['confirm_above_amount'] ?? null;
            $newSecond = (bool) $data['require_second_approval'];

            $needsStepUp = $this->risk->transactionPolicyNeedsStepUp($row, $newConfirm !== '' ? $newConfirm : null, $newSecond);

            if ($needsStepUp) {
                $this->stepUp->assertValidAndConsume($user, $stepUpToken);
            }

            $oldConfirm = $row->confirm_above_amount !== null ? (string) $row->confirm_above_amount : null;
            $oldSecond = $row->require_second_approval;

            $row->confirm_above_amount = $newConfirm !== null && $newConfirm !== '' ? $newConfirm : null;
            $row->fiat_currency = $data['fiat_currency'];
            $row->require_second_approval = $newSecond;
            $row->settings_version = $row->settings_version + 1;
            $row->save();

            $this->audit->logChange($user, 'wallet_tx', $wallet->id, 'confirm_above_amount', $oldConfirm, $row->confirm_above_amount !== null ? (string) $row->confirm_above_amount : null, $request);
            $this->audit->logChange($user, 'wallet_tx', $wallet->id, 'require_second_approval', $oldSecond, $row->require_second_approval, $request);
            $this->audit->logAuditAction($user, 'settings.wallet_transaction_policy.updated', $request, ['wallet_id' => $wallet->id]);

            if ($needsStepUp) {
                $this->notify->notifyTransactionPolicyRelaxed($user, $wallet->id);
            }

            return $this->resolver->resolveWalletSnapshot($user, $wallet);
        });
    }
}

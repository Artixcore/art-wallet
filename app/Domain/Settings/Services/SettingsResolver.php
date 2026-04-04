<?php

namespace App\Domain\Settings\Services;

use App\Models\MessagingPrivacySetting;
use App\Models\RiskThresholdSetting;
use App\Models\User;
use App\Models\UserSecurityPolicy;
use App\Models\UserSetting;
use App\Models\Wallet;
use App\Models\WalletSetting;
use App\Models\WalletTransactionPolicy;
use Illuminate\Support\Facades\DB;

final class SettingsResolver
{
    /**
     * Ensure all user-scoped settings rows exist and return a snapshot for the API.
     *
     * @return array<string, mixed>
     */
    public function resolveUserSnapshot(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $userSetting = UserSetting::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'theme' => 'system',
                    'ui_preferences_version' => 1,
                    'settings_version' => 1,
                ],
            );

            $security = UserSecurityPolicy::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'idle_timeout_minutes' => 60,
                    'notify_new_device_login' => true,
                    'settings_version' => 1,
                ],
            );

            $messaging = MessagingPrivacySetting::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'read_receipts_enabled' => true,
                    'typing_indicators_enabled' => true,
                    'max_attachment_mb' => 10,
                    'safety_warnings_enabled' => true,
                    'discoverable_by_sol_address' => 'off',
                    'require_dm_approval' => false,
                    'hide_profile_until_dm_accepted' => true,
                    'settings_version' => 1,
                ],
            );

            $risk = RiskThresholdSetting::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'large_tx_alert_currency' => 'USD',
                    'settings_version' => 1,
                ],
            );

            return [
                'user' => $this->serializeUserSetting($userSetting),
                'security_policy' => $this->serializeSecurityPolicy($security),
                'messaging_privacy' => $this->serializeMessagingPrivacy($messaging),
                'risk_thresholds' => $this->serializeRiskThresholds($risk),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveWalletSnapshot(User $user, Wallet $wallet): array
    {
        if ((int) $wallet->user_id !== (int) $user->id) {
            abort(404);
        }

        return DB::transaction(function () use ($wallet): array {
            $ws = WalletSetting::query()->firstOrCreate(
                ['wallet_id' => $wallet->id],
                [
                    'show_testnet_assets' => false,
                    'settings_version' => 1,
                ],
            );

            $tx = WalletTransactionPolicy::query()->firstOrCreate(
                ['wallet_id' => $wallet->id],
                [
                    'fiat_currency' => 'USD',
                    'require_second_approval' => false,
                    'settings_version' => 1,
                ],
            );

            return [
                'wallet' => [
                    'id' => $wallet->id,
                    'label' => $wallet->label,
                ],
                'wallet_settings' => [
                    'default_fee_tier' => $ws->default_fee_tier,
                    'show_testnet_assets' => $ws->show_testnet_assets,
                    'settings_version' => $ws->settings_version,
                ],
                'transaction_policy' => [
                    'confirm_above_amount' => $tx->confirm_above_amount !== null ? (string) $tx->confirm_above_amount : null,
                    'fiat_currency' => $tx->fiat_currency,
                    'require_second_approval' => $tx->require_second_approval,
                    'settings_version' => $tx->settings_version,
                ],
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUserSetting(UserSetting $u): array
    {
        return [
            'locale' => $u->locale,
            'timezone' => $u->timezone,
            'theme' => $u->theme,
            'ui_preferences' => $u->ui_preferences_json ?? [],
            'ui_preferences_version' => $u->ui_preferences_version,
            'settings_version' => $u->settings_version,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSecurityPolicy(UserSecurityPolicy $s): array
    {
        return [
            'idle_timeout_minutes' => $s->idle_timeout_minutes,
            'max_session_duration_minutes' => $s->max_session_duration_minutes,
            'notify_new_device_login' => $s->notify_new_device_login,
            'settings_version' => $s->settings_version,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessagingPrivacy(MessagingPrivacySetting $m): array
    {
        return [
            'read_receipts_enabled' => $m->read_receipts_enabled,
            'typing_indicators_enabled' => $m->typing_indicators_enabled,
            'max_attachment_mb' => $m->max_attachment_mb,
            'safety_warnings_enabled' => $m->safety_warnings_enabled,
            'discoverable_by_sol_address' => $m->discoverable_by_sol_address,
            'require_dm_approval' => $m->require_dm_approval,
            'hide_profile_until_dm_accepted' => $m->hide_profile_until_dm_accepted,
            'settings_version' => $m->settings_version,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRiskThresholds(RiskThresholdSetting $r): array
    {
        return [
            'large_tx_alert_fiat' => $r->large_tx_alert_fiat !== null ? (string) $r->large_tx_alert_fiat : null,
            'large_tx_alert_currency' => $r->large_tx_alert_currency,
            'settings_version' => $r->settings_version,
        ];
    }
}

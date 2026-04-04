<?php

namespace App\Domain\Settings\Services;

use App\Models\UserSecurityPolicy;
use App\Models\WalletTransactionPolicy;

final class SettingsRiskEvaluator
{
    public function securityPolicyNeedsStepUp(UserSecurityPolicy $current, int $newIdle, ?int $newMaxSession, bool $newNotifyDevice): bool
    {
        if ($newIdle > $current->idle_timeout_minutes) {
            return true;
        }

        $oldMax = $current->max_session_duration_minutes;
        if ($oldMax !== null && $newMaxSession === null) {
            return true;
        }
        if ($newMaxSession !== null && ($oldMax === null || $newMaxSession > $oldMax)) {
            return true;
        }

        if ($current->notify_new_device_login && ! $newNotifyDevice) {
            return true;
        }

        return false;
    }

    public function transactionPolicyNeedsStepUp(WalletTransactionPolicy $current, ?string $newConfirmAbove, bool $newSecondApproval): bool
    {
        if ($current->require_second_approval && ! $newSecondApproval) {
            return true;
        }

        $old = $current->confirm_above_amount !== null ? (string) $current->confirm_above_amount : null;
        $new = $newConfirmAbove !== null && $newConfirmAbove !== '' ? $newConfirmAbove : null;

        if ($old === null && $new === null) {
            return false;
        }
        if ($old === null && $new !== null) {
            return true;
        }
        if ($old !== null && $new === null) {
            return false;
        }

        return bccomp((string) $new, (string) $old, 8) === 1;
    }

    public function messagingPrivacyNeedsStepUp(bool $currentSafety, bool $newSafety): bool
    {
        return $currentSafety && ! $newSafety;
    }

    public function riskThresholdNeedsStepUp(?string $oldFiat, ?string $newFiat): bool
    {
        if ($oldFiat === null || $newFiat === null) {
            return false;
        }

        return bccomp($newFiat, $oldFiat, 2) === 1;
    }
}

<?php

namespace App\Domain\Settings\Services;

use App\Domain\Notifications\Enums\NotificationCategory;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Notifications\Services\NotificationFactory;
use App\Models\SecurityEvent;
use App\Models\User;

final class SettingsNotificationService
{
    public function __construct(
        private readonly NotificationFactory $notifications,
    ) {}

    public function notifySecurityPolicyRelaxed(User $user): void
    {
        $this->notifications->create(
            $user,
            NotificationCategory::Security,
            NotificationSeverity::Warning,
            'settings.security_policy_relaxed',
            null,
            [
                'dedupe_key' => 'settings:security_policy:'.$user->id.':'.now()->format('Y-m-d-H'),
                'requires_ack' => true,
            ],
        );

        SecurityEvent::query()->create([
            'user_id' => $user->id,
            'event_type' => 'settings.security_policy_relaxed',
            'severity' => 'warning',
            'ip_address' => null,
            'metadata_json' => [],
            'created_at' => now(),
        ]);
    }

    public function notifyTransactionPolicyRelaxed(User $user, int $walletId): void
    {
        $this->notifications->create(
            $user,
            NotificationCategory::Security,
            NotificationSeverity::Warning,
            'settings.transaction_policy_relaxed',
            ['wallet_id' => (string) $walletId],
            [
                'dedupe_key' => 'settings:tx_policy:'.$user->id.':'.$walletId.':'.now()->format('Y-m-d-H'),
                'requires_ack' => true,
                'subject_type' => 'wallet',
                'subject_id' => $walletId,
            ],
        );

        SecurityEvent::query()->create([
            'user_id' => $user->id,
            'event_type' => 'settings.transaction_policy_relaxed',
            'severity' => 'warning',
            'ip_address' => null,
            'metadata_json' => ['wallet_id' => $walletId],
            'created_at' => now(),
        ]);
    }

    public function notifyMessagingPrivacyWeakened(User $user): void
    {
        $this->notifications->create(
            $user,
            NotificationCategory::Security,
            NotificationSeverity::Warning,
            'settings.messaging_privacy_weakened',
            null,
            [
                'dedupe_key' => 'settings:messaging_privacy:'.$user->id.':'.now()->format('Y-m-d-H'),
                'requires_ack' => false,
            ],
        );

        SecurityEvent::query()->create([
            'user_id' => $user->id,
            'event_type' => 'settings.messaging_privacy_weakened',
            'severity' => 'info',
            'ip_address' => null,
            'metadata_json' => [],
            'created_at' => now(),
        ]);
    }

    public function notifyRiskThresholdRaised(User $user): void
    {
        $this->notifications->create(
            $user,
            NotificationCategory::System,
            NotificationSeverity::Info,
            'settings.risk_threshold_relaxed',
            null,
            [
                'dedupe_key' => 'settings:risk_threshold:'.$user->id.':'.now()->format('Y-m-d-H'),
                'requires_ack' => false,
            ],
        );
    }
}

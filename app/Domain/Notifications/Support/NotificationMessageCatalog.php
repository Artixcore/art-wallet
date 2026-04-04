<?php

namespace App\Domain\Notifications\Support;

use App\Domain\Notifications\Enums\NotificationSeverity;

/**
 * Safe, static user-facing copy keyed by title_key. Never interpolate raw exception text.
 */
final class NotificationMessageCatalog
{
    /**
     * @return array{title: string, body: string|null}
     */
    public static function resolve(string $titleKey, ?array $params = null): array
    {
        $params = $params ?? [];

        return match ($titleKey) {
            'tx.broadcast_success' => [
                'title' => __('notification_strings.tx.broadcast_success.title'),
                'body' => isset($params['txid'])
                    ? __('notification_strings.tx.broadcast_success.body', ['txid' => self::truncateMiddle((string) $params['txid'], 12)])
                    : null,
            ],
            'tx.broadcast_failed' => [
                'title' => __('notification_strings.tx.broadcast_failed.title'),
                'body' => __('notification_strings.tx.broadcast_failed.body'),
            ],
            'recovery_kit.saved' => [
                'title' => __('notification_strings.recovery_kit.saved.title'),
                'body' => __('notification_strings.recovery_kit.saved.body'),
            ],
            'security.generic' => [
                'title' => __('notification_strings.security.generic.title'),
                'body' => __('notification_strings.security.generic.body'),
            ],
            'settings.security_policy_relaxed' => [
                'title' => __('notification_strings.settings.security_policy_relaxed.title'),
                'body' => __('notification_strings.settings.security_policy_relaxed.body'),
            ],
            'settings.transaction_policy_relaxed' => [
                'title' => __('notification_strings.settings.transaction_policy_relaxed.title'),
                'body' => isset($params['wallet_id'])
                    ? __('notification_strings.settings.transaction_policy_relaxed.body', ['wallet_id' => (string) $params['wallet_id']])
                    : __('notification_strings.settings.transaction_policy_relaxed.body_fallback'),
            ],
            'settings.messaging_privacy_weakened' => [
                'title' => __('notification_strings.settings.messaging_privacy_weakened.title'),
                'body' => __('notification_strings.settings.messaging_privacy_weakened.body'),
            ],
            'settings.risk_threshold_relaxed' => [
                'title' => __('notification_strings.settings.risk_threshold_relaxed.title'),
                'body' => __('notification_strings.settings.risk_threshold_relaxed.body'),
            ],
            default => [
                'title' => __('notification_strings.fallback.title'),
                'body' => null,
            ],
        };
    }

    public static function severityForKey(string $titleKey): NotificationSeverity
    {
        return match (true) {
            str_starts_with($titleKey, 'tx.broadcast_failed') => NotificationSeverity::Danger,
            str_starts_with($titleKey, 'tx.broadcast_success') => NotificationSeverity::Success,
            str_starts_with($titleKey, 'recovery_kit.') => NotificationSeverity::Success,
            str_starts_with($titleKey, 'settings.security_policy_relaxed'),
            str_starts_with($titleKey, 'settings.transaction_policy_relaxed'),
            str_starts_with($titleKey, 'settings.messaging_privacy_weakened') => NotificationSeverity::Warning,
            default => NotificationSeverity::Info,
        };
    }

    private static function truncateMiddle(string $value, int $edge): string
    {
        if (strlen($value) <= $edge * 2 + 3) {
            return $value;
        }

        return substr($value, 0, $edge).'…'.substr($value, -$edge);
    }
}

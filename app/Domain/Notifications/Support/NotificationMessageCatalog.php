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

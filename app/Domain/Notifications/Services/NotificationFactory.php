<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\Enums\NotificationCategory;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Notifications\Models\InAppNotification;
use App\Domain\Notifications\Support\NotificationMessageCatalog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

final class NotificationFactory
{
    /**
     * @param  array<string, mixed>|null  $bodyParams
     * @param  array{action_url?: string, dedupe_key?: string, subject_type?: string, subject_id?: int, requires_ack?: bool, blocking?: bool, expires_at?: \DateTimeInterface|null}  $options
     */
    public function create(
        User $user,
        NotificationCategory $category,
        NotificationSeverity $severity,
        string $titleKey,
        ?array $bodyParams = null,
        array $options = [],
    ): ?InAppNotification {
        try {
            $payload = [
                'user_id' => $user->id,
                'category' => $category,
                'severity' => $severity,
                'title_key' => $titleKey,
                'body_params' => $bodyParams,
                'action_url' => $options['action_url'] ?? null,
                'dedupe_key' => $options['dedupe_key'] ?? null,
                'subject_type' => $options['subject_type'] ?? null,
                'subject_id' => $options['subject_id'] ?? null,
                'requires_ack' => $options['requires_ack'] ?? false,
                'blocking' => $options['blocking'] ?? false,
                'expires_at' => $options['expires_at'] ?? null,
            ];

            if (($options['dedupe_key'] ?? null) !== null) {
                $existing = InAppNotification::query()
                    ->where('user_id', $user->id)
                    ->where('dedupe_key', $options['dedupe_key'])
                    ->first();
                if ($existing !== null) {
                    $existing->update([
                        'severity' => $payload['severity'],
                        'title_key' => $payload['title_key'],
                        'body_params' => $payload['body_params'],
                        'action_url' => $payload['action_url'],
                        'expires_at' => $payload['expires_at'],
                    ]);

                    return $existing->fresh();
                }
            }

            return InAppNotification::query()->create($payload);
        } catch (QueryException $e) {
            if ($this->isDuplicateDedupe($e)) {
                return InAppNotification::query()
                    ->where('user_id', $user->id)
                    ->where('dedupe_key', $options['dedupe_key'] ?? '')
                    ->first();
            }
            Log::error('notification_create_failed', ['exception' => $e::class, 'user_id' => $user->id]);

            return null;
        }
    }

    /**
     * Convenience: resolve severity from catalog when not overridden.
     *
     * @param  array<string, mixed>|null  $bodyParams
     * @param  array{action_url?: string, dedupe_key?: string, subject_type?: string, subject_id?: int, requires_ack?: bool, blocking?: bool, expires_at?: \DateTimeInterface|null, severity?: NotificationSeverity}  $options
     */
    public function createFromCatalogKey(
        User $user,
        NotificationCategory $category,
        string $titleKey,
        ?array $bodyParams = null,
        array $options = [],
    ): ?InAppNotification {
        $severity = $options['severity'] ?? NotificationMessageCatalog::severityForKey($titleKey);
        unset($options['severity']);

        return $this->create($user, $category, $severity, $titleKey, $bodyParams, $options);
    }

    private function isDuplicateDedupe(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate') || $e->getCode() === '23000';
    }
}

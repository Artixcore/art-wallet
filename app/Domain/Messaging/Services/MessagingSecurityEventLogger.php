<?php

namespace App\Domain\Messaging\Services;

use App\Models\MessagingSecurityEvent;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

final class MessagingSecurityEventLogger
{
    public function log(?User $user, string $type, string $severity, array $meta = []): void
    {
        $key = 'messaging_sec_event:'.($user?->id ?? 'guest');
        $max = (int) config('messaging.security_event_rate_per_minute', 30);
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return;
        }
        RateLimiter::hit($key, 60);

        MessagingSecurityEvent::query()->create([
            'user_id' => $user?->id,
            'type' => $type,
            'severity' => $severity,
            'meta' => $meta,
        ]);
    }
}

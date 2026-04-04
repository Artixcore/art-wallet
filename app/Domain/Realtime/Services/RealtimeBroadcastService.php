<?php

declare(strict_types=1);

namespace App\Domain\Realtime\Services;

use App\Events\Realtime\UserDomainEvent;
use Illuminate\Support\Str;

/**
 * Emits non-authoritative hints on private user channels (WebSocket / Reverb).
 */
final class RealtimeBroadcastService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function publishUserHint(int $userId, string $eventType, array $payload): void
    {
        broadcast(new UserDomainEvent(
            userId: $userId,
            eventId: (string) Str::uuid(),
            eventType: $eventType,
            payload: $payload,
        ));
    }
}

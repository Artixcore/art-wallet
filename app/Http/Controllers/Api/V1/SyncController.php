<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Responses\AjaxEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP sync for clients after reconnect; complements realtime hints.
 */
final class SyncController extends Controller
{
    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'last_event_id' => ['nullable', 'uuid'],
        ]);

        return AjaxEnvelope::ok(
            message: '',
            data: [
                'sync' => true,
                'last_event_id' => $validated['last_event_id'] ?? null,
            ],
            severity: NotificationSeverity::Info,
            meta: [
                'stale' => false,
                'server_time' => now()->toIso8601String(),
            ],
        )->toJsonResponse(200);
    }
}

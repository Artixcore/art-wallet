<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityEventsAjaxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = SecurityEvent::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (SecurityEvent $e) => [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'severity' => $e->severity,
                'metadata_json' => $e->metadata_json,
                'created_at' => $e->created_at?->toIso8601String(),
            ]);

        return response()->json(['ok' => true, 'events' => $events]);
    }
}

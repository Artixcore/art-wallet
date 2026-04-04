<?php

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Models\AlertAcknowledgement;
use App\Domain\Notifications\Models\InAppNotification;
use App\Domain\Notifications\Models\UserNotificationPreference;
use App\Domain\Notifications\Services\NotificationReader;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\AcknowledgeNotificationRequest;
use App\Http\Requests\Ajax\UpdateNotificationPreferencesRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationAjaxController extends Controller
{
    public function index(Request $request, NotificationReader $reader): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $paginator = $reader->paginate($request->user(), $perPage);

        $items = $paginator->getCollection()->map(fn (InAppNotification $n) => AjaxEnvelope::notificationPayload($n));

        return AjaxEnvelope::ok(
            message: '',
            data: [
                'notifications' => $items,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
            meta: ['unread_count' => $reader->unreadCount($request->user())],
        )->toJsonResponse();
    }

    public function dropdown(Request $request, NotificationReader $reader): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 10), 1), 30);
        $rows = $reader->latestDropdown($request->user(), $limit);
        $items = $rows->map(fn (InAppNotification $n) => AjaxEnvelope::notificationPayload($n));

        return AjaxEnvelope::ok(
            message: '',
            data: ['notifications' => $items],
            meta: ['unread_count' => $reader->unreadCount($request->user())],
        )->toJsonResponse();
    }

    public function markRead(Request $request, InAppNotification $in_app_notification): JsonResponse
    {
        $this->authorizeNotification($request, $in_app_notification);

        if ($in_app_notification->read_at === null) {
            $in_app_notification->update(['read_at' => now()]);
        }

        $reader = app(NotificationReader::class);

        return AjaxEnvelope::ok(
            message: '',
            data: ['notification' => AjaxEnvelope::notificationPayload($in_app_notification->fresh())],
            meta: ['unread_count' => $reader->unreadCount($request->user())],
        )->toJsonResponse();
    }

    public function markAllRead(Request $request, NotificationReader $reader): JsonResponse
    {
        InAppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return AjaxEnvelope::ok(
            message: __('Marked all as read.'),
            data: [],
            meta: ['unread_count' => $reader->unreadCount($request->user())],
        )->toJsonResponse();
    }

    public function acknowledge(
        AcknowledgeNotificationRequest $request,
        InAppNotification $in_app_notification,
        NotificationReader $reader,
    ): JsonResponse {
        $this->authorizeNotification($request, $in_app_notification);

        if (! $in_app_notification->requires_ack) {
            return AjaxEnvelope::error(
                AjaxResponseCode::InvalidRequest,
                __('This notification does not require acknowledgment.'),
            )->toJsonResponse(422);
        }

        DB::transaction(function () use ($request, $in_app_notification): void {
            $in_app_notification->update(['acknowledged_at' => now(), 'read_at' => $in_app_notification->read_at ?? now()]);

            $ip = $request->ip();
            $ua = (string) $request->userAgent();

            AlertAcknowledgement::query()->updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'in_app_notification_id' => $in_app_notification->id,
                ],
                [
                    'acknowledged_at' => now(),
                    'ip_hash' => $ip !== null ? hash('sha256', $ip) : null,
                    'user_agent_hash' => $ua !== '' ? hash('sha256', $ua) : null,
                ],
            );
        });

        $fresh = $in_app_notification->fresh();
        if ($fresh === null) {
            abort(500);
        }

        return AjaxEnvelope::ok(
            message: __('Acknowledged.'),
            data: ['notification' => AjaxEnvelope::notificationPayload($fresh)],
            meta: ['unread_count' => $reader->unreadCount($request->user())],
        )->toJsonResponse();
    }

    public function preferences(Request $request): JsonResponse
    {
        $prefs = UserNotificationPreference::query()
            ->where('user_id', $request->user()->id)
            ->get()
            ->map(fn (UserNotificationPreference $p) => [
                'category' => $p->category->value,
                'toast_enabled' => $p->toast_enabled,
                'persist_enabled' => $p->persist_enabled,
                'email_enabled' => $p->email_enabled,
            ]);

        return AjaxEnvelope::ok(
            message: '',
            data: ['preferences' => $prefs],
        )->toJsonResponse();
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $validated = $request->validated();
        foreach ($validated['preferences'] as $row) {
            UserNotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'category' => $row['category'],
                ],
                [
                    'toast_enabled' => $row['toast_enabled'],
                    'persist_enabled' => $row['persist_enabled'],
                    'email_enabled' => $row['email_enabled'],
                ],
            );
        }

        return AjaxEnvelope::ok(
            message: __('Preferences saved.'),
            data: [],
        )->toJsonResponse();
    }

    private function authorizeNotification(Request $request, InAppNotification $notification): void
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            abort(404);
        }
    }
}

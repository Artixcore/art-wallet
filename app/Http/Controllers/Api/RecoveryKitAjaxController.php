<?php

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Enums\NotificationCategory;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Notifications\Services\NotificationFactory;
use App\Domain\Notifications\Services\NotificationReader;
use App\Domain\Notifications\Support\NotificationMessageCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreRecoveryKitRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Models\BackupState;
use App\Models\RecoveryKit;
use App\Services\RecoveryBlobValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecoveryKitAjaxController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $row = RecoveryKit::query()->where('user_id', $request->user()->id)->first();

        if ($row === null) {
            return AjaxEnvelope::ok(
                message: '',
                data: ['recovery_kit' => null],
                severity: NotificationSeverity::Info,
                meta: ['unread_count' => app(NotificationReader::class)->unreadCount($request->user())],
            )->toJsonResponse();
        }

        return AjaxEnvelope::ok(
            message: '',
            data: [
                'recovery_kit' => [
                    'version' => $row->version,
                    'ciphertext' => json_decode($row->ciphertext, true, flags: JSON_THROW_ON_ERROR),
                    'updated_at' => $row->updated_at->toIso8601String(),
                ],
            ],
            severity: NotificationSeverity::Info,
            meta: ['unread_count' => app(NotificationReader::class)->unreadCount($request->user())],
        )->toJsonResponse();
    }

    public function store(
        StoreRecoveryKitRequest $request,
        RecoveryBlobValidator $validator,
        NotificationFactory $notifications,
        NotificationReader $notificationReader,
    ): JsonResponse {
        $validated = $request->validatedKit($validator);
        $envelope = $request->input('recovery_kit');
        if (! is_array($envelope)) {
            return AjaxEnvelope::error(
                AjaxResponseCode::InvalidRequest,
                __('The request was invalid.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(422);
        }

        RecoveryKit::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'version' => (string) $validated['kit_version'],
                'ciphertext' => json_encode($envelope, JSON_THROW_ON_ERROR),
            ],
        );

        BackupState::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['recovery_kit_created_at' => now()],
        );

        $persisted = $notifications->createFromCatalogKey(
            $request->user(),
            NotificationCategory::Recovery,
            'recovery_kit.saved',
            [],
            ['dedupe_key' => 'recovery_kit_saved:'.$request->user()->id],
        );

        $resolved = NotificationMessageCatalog::resolve('recovery_kit.saved');

        return AjaxEnvelope::ok(
            message: $resolved['title'],
            data: [],
            severity: NotificationSeverity::Success,
            toast: AjaxEnvelope::toastPayload(
                $resolved['title'],
                (string) ($resolved['body'] ?? ''),
                NotificationSeverity::Success,
                4000,
                'recovery_kit_saved:'.$request->user()->id,
            ),
            notification: $persisted !== null ? AjaxEnvelope::notificationPayload($persisted) : null,
            meta: ['unread_count' => $notificationReader->unreadCount($request->user())],
        )->toJsonResponse();
    }
}

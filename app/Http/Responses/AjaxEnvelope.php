<?php

namespace App\Http\Responses;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Notifications\Models\InAppNotification;
use App\Domain\Notifications\Support\NotificationMessageCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unified AJAX JSON envelope for ArtWallet.
 *
 * **Observability / operator dashboard `meta` keys** (optional, backward compatible):
 * - `partial` (bool): one or more subsystems failed to load; prefer {@see self::partialSuccess()}.
 * - `stale` (bool): data is older than freshness TTL.
 * - `stale_subsystems` (list<string>): subsystem ids that exceeded TTL.
 * - `subsystem_status` (array<string, string>): map of subsystem id to rollup status (e.g. healthy, stale, unknown).
 * - `actions` (array): e.g. `requires_step_up`, `confirm_token` for remediation flows.
 * - `server_time` (string ISO8601): server snapshot time for the response.
 */
final class AjaxEnvelope
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<int, string>|string>  $errors
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public bool $success,
        public AjaxResponseCode $code,
        public string $message,
        public NotificationSeverity $severity,
        public array $data = [],
        public array $errors = [],
        public array $meta = [],
        public ?array $toast = null,
        public ?array $modal = null,
        public ?array $notification = null,
        public ?string $redirect = null,
        public bool $reload = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public static function ok(
        string $message = '',
        array $data = [],
        NotificationSeverity $severity = NotificationSeverity::Success,
        ?array $toast = null,
        ?array $notification = null,
        array $meta = [],
    ): self {
        $correlationId = (string) Str::uuid();
        $meta = array_merge(['correlation_id' => $correlationId], $meta);

        return new self(
            success: true,
            code: AjaxResponseCode::Ok,
            message: $message,
            severity: $severity,
            data: $data,
            meta: $meta,
            toast: $toast,
            notification: $notification,
        );
    }

    /**
     * Success with {@see AjaxResponseCode::PartialSuccess}: some dashboard sections failed;
     * never implies full system health.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    /**
     * Successful response for a replayed idempotent messaging request (same logical send).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public static function messagingIdempotentReplay(
        string $message,
        array $data = [],
        array $meta = [],
    ): self {
        $correlationId = (string) Str::uuid();
        $meta = array_merge(['correlation_id' => $correlationId], $meta);

        return new self(
            success: true,
            code: AjaxResponseCode::MessagingIdempotencyReplay,
            message: $message,
            severity: NotificationSeverity::Info,
            data: $data,
            meta: $meta,
        );
    }

    public static function partialSuccess(
        string $message,
        array $data = [],
        NotificationSeverity $severity = NotificationSeverity::Warning,
        array $meta = [],
    ): self {
        $correlationId = (string) Str::uuid();
        $meta = array_merge(['correlation_id' => $correlationId], $meta);
        $meta['partial'] = true;

        return new self(
            success: true,
            code: AjaxResponseCode::PartialSuccess,
            message: $message !== '' ? $message : __('Partial data loaded.'),
            severity: $severity,
            data: $data,
            meta: $meta,
        );
    }

    /**
     * @param  array<string, mixed>  $meta  Merged into envelope meta (caller may set partial/stale flags).
     */
    public static function withObservabilityMeta(
        bool $partial,
        bool $stale,
        array $staleSubsystems,
        array $subsystemStatus,
        array $meta = [],
    ): array {
        return array_merge($meta, [
            'partial' => $partial,
            'stale' => $stale,
            'stale_subsystems' => $staleSubsystems,
            'subsystem_status' => $subsystemStatus,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, array<int, string>|string>  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function validationFailed(
        array $errors,
        string $message = '',
        array $meta = [],
    ): self {
        $correlationId = (string) Str::uuid();
        $meta = array_merge(['correlation_id' => $correlationId], $meta);

        return new self(
            success: false,
            code: AjaxResponseCode::ValidationFailed,
            message: $message !== '' ? $message : __('Validation failed.'),
            severity: NotificationSeverity::Danger,
            errors: $errors,
            meta: $meta,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        AjaxResponseCode $code,
        string $message,
        NotificationSeverity $severity = NotificationSeverity::Danger,
        ?array $toast = null,
        ?array $modal = null,
        array $meta = [],
        array $data = [],
    ): self {
        $correlationId = (string) Str::uuid();
        $meta = array_merge(['correlation_id' => $correlationId], $meta);

        return new self(
            success: false,
            code: $code,
            message: $message,
            severity: $severity,
            data: $data,
            meta: $meta,
            toast: $toast,
            modal: $modal,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function serverError(string $correlationId, array $meta = []): self
    {
        $meta = array_merge(['correlation_id' => $correlationId], $meta);

        return new self(
            success: false,
            code: AjaxResponseCode::ServerError,
            message: __('Something went wrong. Reference: :id', ['id' => $correlationId]),
            severity: NotificationSeverity::Danger,
            meta: $meta,
        );
    }

    public function toJsonResponse(int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json($this->toArray(), $status);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'code' => $this->code->value,
            'message' => $this->message,
            'severity' => $this->severity->value,
            'data' => $this->data,
            'errors' => $this->errors,
            'meta' => $this->meta,
            'toast' => $this->toast,
            'modal' => $this->modal,
            'notification' => $this->notification,
            'redirect' => $this->redirect,
            'reload' => $this->reload,
        ];
    }

    /**
     * Build a toast payload for the client.
     *
     * @param  array<string, mixed>  $extra
     */
    public static function toastPayload(
        string $title,
        string $text,
        NotificationSeverity $severity,
        ?int $timerMs = 4000,
        ?string $dedupeKey = null,
        array $extra = [],
    ): array {
        return array_merge([
            'title' => $title,
            'text' => $text,
            'severity' => $severity->value,
            'timer' => $timerMs,
            'dedupe_key' => $dedupeKey,
        ], $extra);
    }

    /**
     * Serialize a persisted in-app notification for the envelope.
     *
     * @return array<string, mixed>
     */
    public static function notificationPayload(InAppNotification $n): array
    {
        $resolved = NotificationMessageCatalog::resolve($n->title_key, $n->body_params);

        return [
            'id' => $n->id,
            'category' => $n->category->value,
            'severity' => $n->severity->value,
            'title' => $resolved['title'],
            'body' => $resolved['body'],
            'title_key' => $n->title_key,
            'action_url' => $n->action_url,
            'read_at' => $n->read_at?->toIso8601String(),
            'requires_ack' => $n->requires_ack,
            'acknowledged_at' => $n->acknowledged_at?->toIso8601String(),
            'blocking' => $n->blocking,
            'created_at' => $n->created_at->toIso8601String(),
        ];
    }
}

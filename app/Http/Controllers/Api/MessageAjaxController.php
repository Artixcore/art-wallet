<?php

namespace App\Http\Controllers\Api;

use App\Domain\Messaging\Actions\AppendMessageAction;
use App\Domain\Messaging\Services\MessagingDeliveryService;
use App\Domain\Messaging\Services\MessagingSecurityEventLogger;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreMessageRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Services\CryptoEnvelopeValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MessageAjaxController extends Controller
{
    public function index(
        Request $request,
        MessagingDeliveryService $delivery,
        int $conversation,
    ): JsonResponse {
        $userId = (int) $request->user()->id;

        $member = ConversationMember::query()
            ->where('conversation_id', $conversation)
            ->where('user_id', $userId)
            ->first();

        if ($member === null) {
            return AjaxEnvelope::error(
                AjaxResponseCode::Forbidden,
                __('You are not a member of this conversation.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(403);
        }

        $beforeIndex = $request->query('before_index');
        $query = Message::query()
            ->where('conversation_id', $conversation)
            ->with(['deliveryStates', 'attachment'])
            ->orderByDesc('message_index');

        if ($beforeIndex !== null && $beforeIndex !== '') {
            $query->where('message_index', '<', (int) $beforeIndex);
        }

        $messages = $query->limit(50)->get();

        $ids = $messages->pluck('id')->map(fn ($id) => (int) $id)->all();
        $delivery->markDeliveredForRecipient($userId, $ids);

        $payload = $messages->map(fn (Message $m) => $this->serializeMessage($m, $userId));

        return AjaxEnvelope::ok(
            '',
            data: ['messages' => $payload->values()->all()],
            meta: [
                'retryable' => false,
                'integrity_status' => 'unknown',
                'delivery_state' => 'synced',
            ],
        )->toJsonResponse();
    }

    public function store(
        StoreMessageRequest $request,
        CryptoEnvelopeValidator $crypto,
        AppendMessageAction $append,
        MessagingSecurityEventLogger $security,
        int $conversation,
    ): JsonResponse {
        $userId = (int) $request->user()->id;

        $member = ConversationMember::query()
            ->where('conversation_id', $conversation)
            ->where('user_id', $userId)
            ->first();

        if ($member === null) {
            return AjaxEnvelope::error(
                AjaxResponseCode::Forbidden,
                __('You are not a member of this conversation.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(403);
        }

        $row = $request->validated();

        try {
            $crypto->validateMessageCipher(
                $row['nonce'],
                $row['ciphertext'],
                $row['alg'],
                $row['version']
            );
        } catch (ValidationException $e) {
            $security->log($request->user(), 'invalid_message_cipher', 'warning', ['conversation_id' => $conversation]);

            return AjaxEnvelope::error(
                AjaxResponseCode::CryptoEnvelopeInvalid,
                __('Message encryption envelope is invalid or unsupported.'),
                NotificationSeverity::Danger,
                meta: [
                    'retryable' => false,
                    'client_behavior' => 'none',
                    'integrity_status' => 'tampered',
                ],
            )->toJsonResponse(422);
        }

        $conversationModel = $member->conversation;
        if ($conversationModel === null) {
            return AjaxEnvelope::error(
                AjaxResponseCode::MessagingConversationNotFound,
                __('Conversation not found.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(404);
        }

        $result = $append->execute($conversationModel, $request->user(), $row);
        $message = $result['message'];
        $replay = $result['idempotent_replay'];

        if ($replay) {
            return AjaxEnvelope::messagingIdempotentReplay(
                __('Message already recorded (idempotent).'),
                [
                    'message_id' => $message->id,
                    'message_index' => $message->message_index,
                    'server_time' => $message->created_at?->toIso8601String(),
                ],
                [
                    'retryable' => false,
                    'idempotent_replay' => true,
                ],
            )->toJsonResponse();
        }

        if ($request->hasFile('attachment_ciphertext')) {
            $manifest = $row['enc_manifest'] ?? null;
            if (! is_array($manifest)) {
                return AjaxEnvelope::error(
                    AjaxResponseCode::CryptoEnvelopeInvalid,
                    __('Attachment manifest is required for file ciphertext.'),
                    NotificationSeverity::Danger,
                )->toJsonResponse(422);
            }
            try {
                $crypto->validateAttachmentManifest($manifest);
            } catch (ValidationException) {
                $security->log($request->user(), 'invalid_attachment_manifest', 'warning', ['conversation_id' => $conversation]);

                return AjaxEnvelope::error(
                    AjaxResponseCode::CryptoEnvelopeInvalid,
                    __('Attachment manifest failed validation.'),
                    NotificationSeverity::Danger,
                )->toJsonResponse(422);
            }

            $mimeHint = $request->input('mime_hint');
            $allowed = config('messaging.attachment_mime_hints_allowed', []);
            if ($mimeHint !== null && $mimeHint !== '' && ! in_array($mimeHint, $allowed, true)) {
                return AjaxEnvelope::error(
                    AjaxResponseCode::PolicyRejected,
                    __('This attachment type is not allowed.'),
                    NotificationSeverity::Warning,
                )->toJsonResponse(422);
            }

            $file = $request->file('attachment_ciphertext');
            if ($file === null) {
                return AjaxEnvelope::error(
                    AjaxResponseCode::AttachmentUploadFailed,
                    __('Attachment upload failed.'),
                    NotificationSeverity::Danger,
                )->toJsonResponse(422);
            }

            $contents = $file->getContent();
            $max = (int) config('messaging.max_attachment_bytes', 26214400);
            if (strlen($contents) > $max) {
                return AjaxEnvelope::error(
                    AjaxResponseCode::AttachmentQuotaExceeded,
                    __('Attachment exceeds the maximum size.'),
                    NotificationSeverity::Warning,
                )->toJsonResponse(422);
            }

            $sha = hash('sha256', $contents);
            $path = 'messaging/attachments/'.$message->id.'/'.$sha;
            $stored = Storage::disk('local')->put($path, $contents);
            if ($stored === false) {
                return AjaxEnvelope::error(
                    AjaxResponseCode::AttachmentUploadFailed,
                    __('Encrypted attachment could not be stored.'),
                    NotificationSeverity::Danger,
                    meta: [
                        'partial_success' => true,
                        'message_persisted' => true,
                        'message_id' => $message->id,
                    ],
                )->toJsonResponse(503);
            }

            MessageAttachment::query()->create([
                'message_id' => $message->id,
                'storage_path' => $path,
                'size_bytes' => strlen($contents),
                'content_type' => $mimeHint ?? 'application/octet-stream',
                'mime_hint' => $mimeHint,
                'enc_manifest' => json_encode($manifest, JSON_THROW_ON_ERROR),
                'ciphertext_sha256' => $sha,
                'upload_state' => MessageAttachment::UploadComplete,
            ]);
        }

        return AjaxEnvelope::ok(
            __('Message sent.'),
            data: [
                'message_id' => $message->id,
                'message_index' => $message->message_index,
                'server_time' => $message->created_at?->toIso8601String(),
                'ciphertext_sha256' => $message->ciphertext_sha256,
                'attachment_uploaded' => $request->hasFile('attachment_ciphertext'),
            ],
            meta: [
                'retryable' => false,
                'integrity_status' => 'ok',
                'delivery_state' => 'pending',
                'partial_attachment_failure' => false,
            ],
        )->toJsonResponse();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(Message $m, int $viewerId): array
    {
        $delivery = $m->deliveryStates->firstWhere('recipient_user_id', $viewerId);

        return [
            'id' => $m->id,
            'conversation_id' => $m->conversation_id,
            'sender_id' => $m->sender_id,
            'message_index' => $m->message_index,
            'ciphertext' => $m->ciphertext,
            'ciphertext_sha256' => $m->ciphertext_sha256,
            'nonce' => $m->nonce,
            'alg' => $m->alg,
            'version' => $m->version,
            'client_message_id' => $m->client_message_id,
            'sent_at' => $m->sent_at?->toIso8601String(),
            'created_at' => $m->created_at?->toIso8601String(),
            'delivery' => $delivery !== null ? [
                'state' => $delivery->state,
            ] : null,
            'attachment' => $m->attachment !== null ? [
                'id' => $m->attachment->id,
                'size_bytes' => $m->attachment->size_bytes,
                'mime_hint' => $m->attachment->mime_hint ?? $m->attachment->content_type,
                'upload_state' => $m->attachment->upload_state,
                'ciphertext_sha256' => $m->attachment->ciphertext_sha256,
            ] : null,
        ];
    }
}

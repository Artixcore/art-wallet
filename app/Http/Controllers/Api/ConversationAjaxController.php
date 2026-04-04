<?php

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreConversationRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\User;
use App\Services\CryptoEnvelopeValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationAjaxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $rows = Conversation::query()
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->with(['members' => fn ($q) => $q->where('user_id', $userId)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $data = $rows->map(function (Conversation $c) {
            /** @var ConversationMember|null $myMembership */
            $myMembership = $c->members->first();
            $maxIndex = Message::query()->where('conversation_id', $c->id)->max('message_index');
            $lastReadIndex = null;
            if ($myMembership?->last_read_message_id !== null) {
                $lastReadIndex = Message::query()->whereKey($myMembership->last_read_message_id)->value('message_index');
            }
            $unread = 0;
            if ($maxIndex !== null) {
                $lastReadIndex = $lastReadIndex ?? -1;
                $unread = max(0, (int) $maxIndex - (int) $lastReadIndex);
            }

            return [
                'conversation_id' => $c->id,
                'public_id' => $c->public_id,
                'type' => $c->type,
                'ck_version' => $c->ck_version ?? 1,
                'last_message_at' => $c->last_message_at?->toIso8601String(),
                'unread_count' => $unread,
                'preview_placeholder' => true,
            ];
        });

        return AjaxEnvelope::ok(
            '',
            data: ['conversations' => $data->values()->all()],
            meta: [
                'retryable' => false,
                'integrity_status' => 'unknown',
            ],
        )->toJsonResponse();
    }

    public function store(StoreConversationRequest $request, CryptoEnvelopeValidator $crypto): JsonResponse
    {
        $wraps = $request->validatedWraps($crypto);
        $creatorId = (int) $request->user()->id;

        foreach ($wraps as $w) {
            $user = User::query()->find($w['user_id']);
            if ($user === null || $user->messaging_x25519_public_key === null) {
                return AjaxEnvelope::error(
                    AjaxResponseCode::MessagingKeyRequired,
                    __('All members must register a messaging public key first.'),
                    NotificationSeverity::Warning,
                    meta: [
                        'retryable' => false,
                        'client_behavior' => 'rekey',
                    ],
                )->toJsonResponse(422);
            }
        }

        $type = (string) ($request->input('type') ?: 'direct');
        if ($type === 'direct' && count($wraps) !== 2) {
            return AjaxEnvelope::error(
                AjaxResponseCode::InvalidRequest,
                __('Direct conversations require exactly two members.'),
                NotificationSeverity::Warning,
            )->toJsonResponse(422);
        }

        $conversation = DB::transaction(function () use ($request, $wraps, $creatorId, $type) {
            $conv = Conversation::query()->create([
                'type' => $type,
                'public_id' => $request->input('public_id'),
                'ck_version' => 1,
            ]);

            foreach ($wraps as $w) {
                ConversationMember::query()->create([
                    'conversation_id' => $conv->id,
                    'user_id' => $w['user_id'],
                    'role' => $w['user_id'] === $creatorId ? 'owner' : 'member',
                    'wrapped_conv_key_ciphertext' => $w['wrapped_json'],
                    'wrapped_ck_version' => 1,
                ]);
            }

            return $conv;
        });

        return AjaxEnvelope::ok(
            __('Conversation created.'),
            data: [
                'conversation_id' => $conversation->id,
                'public_id' => $conversation->public_id,
            ],
            meta: [
                'retryable' => false,
                'conversation_state' => [
                    'conversation_id' => $conversation->id,
                    'ck_version' => $conversation->ck_version ?? 1,
                ],
            ],
        )->toJsonResponse();
    }
}

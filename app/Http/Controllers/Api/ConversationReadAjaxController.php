<?php

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\UpdateConversationReadRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Models\ConversationMember;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class ConversationReadAjaxController extends Controller
{
    public function update(UpdateConversationReadRequest $request, int $conversation): JsonResponse
    {
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

        $message = Message::query()
            ->where('id', (int) $request->validated('last_read_message_id'))
            ->where('conversation_id', $conversation)
            ->first();

        if ($message === null) {
            return AjaxEnvelope::error(
                AjaxResponseCode::InvalidRequest,
                __('That message does not belong to this conversation.'),
                NotificationSeverity::Warning,
            )->toJsonResponse(422);
        }

        $prev = $member->last_read_message_id !== null
            ? Message::query()->whereKey($member->last_read_message_id)->value('message_index')
            : null;
        if ($prev !== null && $message->message_index < $prev) {
            return AjaxEnvelope::error(
                AjaxResponseCode::PolicyRejected,
                __('Read cursor cannot move backwards.'),
                NotificationSeverity::Warning,
            )->toJsonResponse(422);
        }

        $member->update([
            'last_read_message_id' => $message->id,
            'last_read_at' => now(),
        ]);

        return AjaxEnvelope::ok(
            __('Read state updated.'),
            data: [
                'last_read_message_id' => $message->id,
                'last_read_message_index' => $message->message_index,
            ],
            meta: [
                'retryable' => false,
                'conversation_state' => [
                    'conversation_id' => $conversation,
                ],
            ],
        )->toJsonResponse();
    }
}

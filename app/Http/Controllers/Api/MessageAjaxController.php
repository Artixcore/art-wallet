<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreMessageRequest;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Services\CryptoEnvelopeValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MessageAjaxController extends Controller
{
    public function store(
        StoreMessageRequest $request,
        CryptoEnvelopeValidator $crypto,
        int $conversationId
    ): JsonResponse {
        $userId = (int) $request->user()->id;

        $member = ConversationMember::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if ($member === null) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $row = $request->validated();
        $crypto->validateMessageCipher(
            $row['nonce'],
            $row['ciphertext'],
            $row['alg'],
            $row['version']
        );

        $message = DB::transaction(function () use ($conversationId, $userId, $row) {
            $max = Message::query()
                ->where('conversation_id', $conversationId)
                ->max('message_index');
            $nextIndex = $max === null ? 0 : ((int) $max) + 1;

            return Message::query()->create([
                'conversation_id' => $conversationId,
                'sender_id' => $userId,
                'message_index' => $nextIndex,
                'ciphertext' => $row['ciphertext'],
                'nonce' => $row['nonce'],
                'alg' => $row['alg'],
                'version' => $row['version'],
            ]);
        });

        return response()->json([
            'ok' => true,
            'message_index' => $message->message_index,
        ]);
    }
}

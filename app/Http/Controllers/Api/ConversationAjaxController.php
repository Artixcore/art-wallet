<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\User;
use App\Services\CryptoEnvelopeValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ConversationAjaxController extends Controller
{
    public function store(StoreConversationRequest $request, CryptoEnvelopeValidator $crypto): JsonResponse
    {
        $wraps = $request->validatedWraps($crypto);
        $creatorId = (int) $request->user()->id;

        foreach ($wraps as $w) {
            $user = User::query()->find($w['user_id']);
            if ($user === null || $user->messaging_x25519_public_key === null) {
                return response()->json([
                    'message' => 'All members must register a messaging public key first.',
                ], 422);
            }
        }

        $conversation = DB::transaction(function () use ($request, $wraps, $creatorId) {
            $conv = Conversation::query()->create([
                'type' => $request->input('type', 'direct'),
                'public_id' => $request->input('public_id'),
            ]);

            foreach ($wraps as $w) {
                ConversationMember::query()->create([
                    'conversation_id' => $conv->id,
                    'user_id' => $w['user_id'],
                    'role' => $w['user_id'] === $creatorId ? 'owner' : 'member',
                    'wrapped_conv_key_ciphertext' => $w['wrapped_json'],
                ]);
            }

            return $conv;
        });

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversation->id,
        ]);
    }
}

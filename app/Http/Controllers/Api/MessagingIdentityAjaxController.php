<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\UpdateMessagingIdentityRequest;
use Illuminate\Http\JsonResponse;

class MessagingIdentityAjaxController extends Controller
{
    public function update(UpdateMessagingIdentityRequest $request): JsonResponse
    {
        $b64 = $request->input('messaging_x25519_public_key');
        $raw = base64_decode((string) $b64, true);
        if ($raw === false || strlen($raw) !== 32) {
            return response()->json(['message' => 'Invalid public key encoding.'], 422);
        }

        $request->user()->forceFill([
            'messaging_x25519_public_key' => $b64,
        ])->save();

        return response()->json(['ok' => true]);
    }
}

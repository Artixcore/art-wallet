<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreRecoveryKitRequest;
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
            return response()->json(['ok' => true, 'recovery_kit' => null]);
        }

        return response()->json([
            'ok' => true,
            'recovery_kit' => [
                'version' => $row->version,
                'ciphertext' => json_decode($row->ciphertext, true, flags: JSON_THROW_ON_ERROR),
                'updated_at' => $row->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function store(StoreRecoveryKitRequest $request, RecoveryBlobValidator $validator): JsonResponse
    {
        $validated = $request->validatedKit($validator);
        $envelope = $request->input('recovery_kit');
        if (! is_array($envelope)) {
            return response()->json(['ok' => false, 'error' => 'invalid_request'], 422);
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

        return response()->json(['ok' => true]);
    }
}

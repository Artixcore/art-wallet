<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\UpdateBackupStateRequest;
use App\Models\BackupState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupStateAjaxController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $state = BackupState::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['strict_security_mode' => false],
        );

        return response()->json([
            'ok' => true,
            'backup_state' => [
                'mnemonic_verified_at' => $state->mnemonic_verified_at?->toIso8601String(),
                'recovery_kit_created_at' => $state->recovery_kit_created_at?->toIso8601String(),
                'server_backup_uploaded_at' => $state->server_backup_uploaded_at?->toIso8601String(),
                'hint_public' => $state->hint_public,
                'strict_security_mode' => $state->strict_security_mode,
            ],
        ]);
    }

    public function update(UpdateBackupStateRequest $request): JsonResponse
    {
        $state = BackupState::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['strict_security_mode' => false],
        );

        if ($request->has('mnemonic_verified') && $request->boolean('mnemonic_verified')) {
            $state->mnemonic_verified_at = now();
        }

        if ($request->has('strict_security_mode')) {
            $state->strict_security_mode = $request->boolean('strict_security_mode');
        }

        if ($request->has('hint_public')) {
            $state->hint_public = $request->validated('hint_public');
        }

        $state->save();

        return response()->json(['ok' => true]);
    }
}

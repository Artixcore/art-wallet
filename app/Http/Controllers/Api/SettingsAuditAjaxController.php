<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\AjaxEnvelope;
use App\Models\SettingsChangeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsAuditAjaxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = SettingsChangeLog::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (SettingsChangeLog $log) => [
                'id' => $log->id,
                'scope' => $log->scope,
                'wallet_id' => $log->wallet_id,
                'setting_key' => $log->setting_key,
                'old_value' => $log->old_value,
                'new_value' => $log->new_value,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return AjaxEnvelope::ok(
            message: '',
            data: ['logs' => $rows],
        )->toJsonResponse();
    }
}

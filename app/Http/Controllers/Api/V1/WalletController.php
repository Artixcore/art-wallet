<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Responses\AjaxEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = $request->user()->wallets()
            ->orderByDesc('id')
            ->get(['id', 'label', 'public_wallet_id', 'vault_version']);

        return AjaxEnvelope::ok(
            message: '',
            data: ['wallets' => $rows],
            severity: NotificationSeverity::Info,
            meta: ['server_time' => now()->toIso8601String()],
        )->toJsonResponse(200);
    }
}

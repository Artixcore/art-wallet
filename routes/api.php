<?php

declare(strict_types=1);

use App\Http\Controllers\Api\InboundWebhookController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BroadcastController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TransactionIntentController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/inbound/{integrationEndpoint}', [InboundWebhookController::class, 'verify'])
    ->middleware('throttle:webhook-inbound')
    ->name('api.webhooks.inbound.verify');

Route::prefix('v1')->group(function (): void {
    Route::middleware(['throttle:api-auth'])->group(function (): void {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    });

    Route::middleware(['throttle:api-refresh'])->group(function (): void {
        Route::post('/auth/refresh', [AuthController::class, 'refresh'])->name('api.v1.auth.refresh');
    });

    Route::middleware(['auth:sanctum', 'api.device', 'verified'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        Route::get('/me', [MeController::class, 'show'])->name('api.v1.me');

        Route::get('/wallets', [WalletController::class, 'index'])->name('api.v1.wallets.index');

        Route::post('/sync/events', [SyncController::class, 'events'])
            ->middleware('throttle:api-sync')
            ->name('api.v1.sync.events');

        Route::middleware(['throttle:api-tx-intent'])->group(function (): void {
            Route::post('/wallets/{wallet}/transaction-intents', [TransactionIntentController::class, 'store'])
                ->whereNumber('wallet')
                ->name('api.v1.transaction-intents.store');
            Route::get('/wallets/{wallet}/transaction-intents/{intent}', [TransactionIntentController::class, 'show'])
                ->whereNumber(['wallet', 'intent'])
                ->name('api.v1.transaction-intents.show');
        });

        Route::post('/wallets/{wallet}/transaction-intents/{intent}/broadcast', [BroadcastController::class, 'store'])
            ->whereNumber(['wallet', 'intent'])
            ->middleware('throttle:api-broadcast')
            ->name('api.v1.transaction-intents.broadcast');
    });
});

<?php

use App\Http\Controllers\Api\ConversationAjaxController;
use App\Http\Controllers\Api\HealthAjaxController;
use App\Http\Controllers\Api\MessageAjaxController;
use App\Http\Controllers\Api\MessagingIdentityAjaxController;
use App\Http\Controllers\Api\WalletAjaxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'throttle:120,1'])->prefix('ajax')->group(function () {
    Route::get('/health', [HealthAjaxController::class, 'show'])->name('ajax.health');
});

Route::middleware(['auth', 'verified', 'throttle:12,1'])->prefix('ajax')->group(function () {
    Route::post('/wallets', [WalletAjaxController::class, 'store'])->name('ajax.wallets.store');
});

Route::middleware(['auth', 'verified', 'throttle:30,1'])->prefix('ajax')->group(function () {
    Route::put('/messaging/identity', [MessagingIdentityAjaxController::class, 'update'])->name('ajax.messaging.identity');
    Route::post('/conversations', [ConversationAjaxController::class, 'store'])->name('ajax.conversations.store');
    Route::post('/conversations/{conversation}/messages', [MessageAjaxController::class, 'store'])
        ->whereNumber('conversation')
        ->name('ajax.conversations.messages.store');
});

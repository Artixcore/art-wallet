<?php

use App\Http\Controllers\Api\BackupStateAjaxController;
use App\Http\Controllers\Api\ConversationAjaxController;
use App\Http\Controllers\Api\DeviceChallengeAjaxController;
use App\Http\Controllers\Api\HealthAjaxController;
use App\Http\Controllers\Api\MessageAjaxController;
use App\Http\Controllers\Api\MessagingIdentityAjaxController;
use App\Http\Controllers\Api\RecoveryKitAjaxController;
use App\Http\Controllers\Api\SecurityEventsAjaxController;
use App\Http\Controllers\Api\SessionSecurityAjaxController;
use App\Http\Controllers\Api\TrustedDeviceAjaxController;
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

Route::middleware(['auth', 'verified', 'throttle:60,1'])->prefix('ajax/security')->group(function () {
    Route::get('/trusted-devices', [TrustedDeviceAjaxController::class, 'index'])->name('ajax.security.trusted-devices.index');
    Route::post('/trusted-devices', [TrustedDeviceAjaxController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('ajax.security.trusted-devices.store');
    Route::delete('/trusted-devices/{login_trusted_device}', [TrustedDeviceAjaxController::class, 'destroy'])
        ->name('ajax.security.trusted-devices.destroy');

    Route::post('/challenges', [DeviceChallengeAjaxController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('ajax.security.challenges.store');
    Route::get('/challenges/pending', [DeviceChallengeAjaxController::class, 'pending'])->name('ajax.security.challenges.pending');
    Route::post('/challenges/approve', [DeviceChallengeAjaxController::class, 'approve'])
        ->middleware('throttle:30,1')
        ->name('ajax.security.challenges.approve');
    Route::get('/challenges/status', [DeviceChallengeAjaxController::class, 'status'])->name('ajax.security.challenges.status');

    Route::get('/sessions', [SessionSecurityAjaxController::class, 'index'])->name('ajax.security.sessions.index');
    Route::delete('/sessions/{user_session_record}', [SessionSecurityAjaxController::class, 'revoke'])
        ->name('ajax.security.sessions.revoke');
    Route::post('/sessions/revoke-others', [SessionSecurityAjaxController::class, 'revokeOthers'])
        ->middleware('throttle:12,1')
        ->name('ajax.security.sessions.revoke-others');

    Route::get('/recovery-kit', [RecoveryKitAjaxController::class, 'show'])->name('ajax.security.recovery-kit.show');
    Route::post('/recovery-kit', [RecoveryKitAjaxController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('ajax.security.recovery-kit.store');

    Route::get('/backup-state', [BackupStateAjaxController::class, 'show'])->name('ajax.security.backup-state.show');
    Route::post('/backup-state', [BackupStateAjaxController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('ajax.security.backup-state.update');

    Route::get('/events', [SecurityEventsAjaxController::class, 'index'])->name('ajax.security.events.index');
});

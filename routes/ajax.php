<?php

use App\Http\Controllers\Api\BackupStateAjaxController;
use App\Http\Controllers\Api\BroadcastAjaxController;
use App\Http\Controllers\Api\ConversationAjaxController;
use App\Http\Controllers\Api\ConversationReadAjaxController;
use App\Http\Controllers\Api\DeviceChallengeAjaxController;
use App\Http\Controllers\Api\FeeEstimateAjaxController;
use App\Http\Controllers\Api\HealthAjaxController;
use App\Http\Controllers\Api\MessageAjaxController;
use App\Http\Controllers\Api\MessagingDeviceAjaxController;
use App\Http\Controllers\Api\MessagingIdentityAjaxController;
use App\Http\Controllers\Api\NetworkMetadataAjaxController;
use App\Http\Controllers\Api\NotificationAjaxController;
use App\Http\Controllers\Api\OperatorDashboardAjaxController;
use App\Http\Controllers\Api\RecoveryKitAjaxController;
use App\Http\Controllers\Api\SecurityEventsAjaxController;
use App\Http\Controllers\Api\SessionSecurityAjaxController;
use App\Http\Controllers\Api\SettingsAjaxController;
use App\Http\Controllers\Api\SettingsAuditAjaxController;
use App\Http\Controllers\Api\TransactionHistoryAjaxController;
use App\Http\Controllers\Api\TransactionIntentAjaxController;
use App\Http\Controllers\Api\TrustedDeviceAjaxController;
use App\Http\Controllers\Api\WalletAddressAjaxController;
use App\Http\Controllers\Api\WalletAjaxController;
use App\Http\Controllers\Api\WalletListAjaxController;
use App\Http\Controllers\Api\WalletSettingsAjaxController;
use App\Http\Controllers\Api\WalletVaultAjaxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'throttle:120,1'])->prefix('ajax')->group(function () {
    Route::get('/health', [HealthAjaxController::class, 'show'])->name('ajax.health');
});

Route::middleware(['auth', 'verified', 'throttle:30,1'])->prefix('ajax/operator')->group(function () {
    Route::get('/summary', [OperatorDashboardAjaxController::class, 'summary'])->name('ajax.operator.summary');
    Route::post('/probes/run', [OperatorDashboardAjaxController::class, 'runProbes'])
        ->middleware('throttle:12,1')
        ->name('ajax.operator.probes.run');
});

Route::middleware(['auth', 'verified', 'throttle:60,1'])->prefix('ajax/settings')->group(function () {
    Route::get('/', [SettingsAjaxController::class, 'show'])->name('ajax.settings.show');
    Route::put('/user', [SettingsAjaxController::class, 'updateUser'])->name('ajax.settings.user.update');
    Route::put('/security-policy', [SettingsAjaxController::class, 'updateSecurityPolicy'])->name('ajax.settings.security-policy.update');
    Route::put('/messaging-privacy', [SettingsAjaxController::class, 'updateMessagingPrivacy'])->name('ajax.settings.messaging-privacy.update');
    Route::put('/risk-thresholds', [SettingsAjaxController::class, 'updateRiskThresholds'])->name('ajax.settings.risk-thresholds.update');
    Route::get('/audit', [SettingsAuditAjaxController::class, 'index'])->name('ajax.settings.audit.index');
    Route::post('/step-up', [SettingsAjaxController::class, 'stepUp'])
        ->middleware('throttle:12,1')
        ->name('ajax.settings.step-up');
});

Route::middleware(['auth', 'verified', 'throttle:60,1'])->prefix('ajax')->group(function () {
    Route::get('/notifications', [NotificationAjaxController::class, 'index'])->name('ajax.notifications.index');
    Route::get('/notifications/dropdown', [NotificationAjaxController::class, 'dropdown'])->name('ajax.notifications.dropdown');
    Route::post('/notifications/read-all', [NotificationAjaxController::class, 'markAllRead'])->name('ajax.notifications.read-all');
    Route::post('/notifications/{in_app_notification}/read', [NotificationAjaxController::class, 'markRead'])
        ->whereNumber('in_app_notification')
        ->name('ajax.notifications.read');
    Route::post('/notifications/{in_app_notification}/acknowledge', [NotificationAjaxController::class, 'acknowledge'])
        ->whereNumber('in_app_notification')
        ->name('ajax.notifications.acknowledge');
    Route::get('/notifications/preferences', [NotificationAjaxController::class, 'preferences'])->name('ajax.notifications.preferences.show');
    Route::put('/notifications/preferences', [NotificationAjaxController::class, 'updatePreferences'])->name('ajax.notifications.preferences.update');
});

Route::middleware(['auth', 'verified', 'throttle:12,1'])->prefix('ajax')->group(function () {
    Route::post('/wallets', [WalletAjaxController::class, 'store'])->name('ajax.wallets.store');
});

Route::middleware(['auth', 'verified', 'throttle:60,1'])->prefix('ajax')->group(function () {
    Route::get('/networks', [NetworkMetadataAjaxController::class, 'index'])->name('ajax.networks.index');
    Route::get('/wallets/list', [WalletListAjaxController::class, 'index'])->name('ajax.wallets.index');
    Route::get('/wallets/{wallet}/vault', [WalletVaultAjaxController::class, 'show'])
        ->whereNumber('wallet')
        ->name('ajax.wallets.vault.show');
    Route::get('/fee-estimates', [FeeEstimateAjaxController::class, 'show'])->name('ajax.fee-estimates.show');
    Route::get('/wallets/{wallet}/settings-bundle', [WalletSettingsAjaxController::class, 'show'])
        ->whereNumber('wallet')
        ->name('ajax.wallets.settings-bundle.show');
    Route::put('/wallets/{wallet}/settings-bundle', [WalletSettingsAjaxController::class, 'updateWallet'])
        ->whereNumber('wallet')
        ->name('ajax.wallets.settings-bundle.update');
    Route::put('/wallets/{wallet}/transaction-policy', [WalletSettingsAjaxController::class, 'updateTransactionPolicy'])
        ->whereNumber('wallet')
        ->name('ajax.wallets.transaction-policy.update');
});

Route::middleware(['auth', 'verified', 'throttle:30,1'])->prefix('ajax')->group(function () {
    Route::post('/wallets/{wallet}/addresses', [WalletAddressAjaxController::class, 'sync'])
        ->whereNumber('wallet')
        ->name('ajax.wallets.addresses.sync');
    Route::post('/wallets/{wallet}/transaction-intents', [TransactionIntentAjaxController::class, 'store'])
        ->whereNumber('wallet')
        ->middleware('throttle:20,1')
        ->name('ajax.wallets.transaction-intents.store');
    Route::get('/wallets/{wallet}/transaction-intents/{intent}', [TransactionIntentAjaxController::class, 'show'])
        ->whereNumber('wallet')
        ->whereNumber('intent')
        ->name('ajax.wallets.transaction-intents.show');
    Route::post('/wallets/{wallet}/transaction-intents/{intent}/broadcast', [BroadcastAjaxController::class, 'store'])
        ->whereNumber('wallet')
        ->whereNumber('intent')
        ->middleware('throttle:12,1')
        ->name('ajax.wallets.transaction-intents.broadcast');
    Route::get('/wallets/{wallet}/blockchain-transactions', [TransactionHistoryAjaxController::class, 'index'])
        ->whereNumber('wallet')
        ->name('ajax.wallets.blockchain-transactions.index');
    Route::get('/wallets/{wallet}/blockchain-transactions/{blockchain_transaction}', [TransactionHistoryAjaxController::class, 'show'])
        ->whereNumber('wallet')
        ->whereNumber('blockchain_transaction')
        ->name('ajax.wallets.blockchain-transactions.show');
});

Route::middleware(['auth', 'verified', 'throttle:30,1'])->prefix('ajax')->group(function () {
    Route::put('/messaging/identity', [MessagingIdentityAjaxController::class, 'update'])->name('ajax.messaging.identity');
    Route::post('/messaging/devices', [MessagingDeviceAjaxController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('ajax.messaging.devices.store');
    Route::get('/conversations', [ConversationAjaxController::class, 'index'])->name('ajax.conversations.index');
    Route::post('/conversations', [ConversationAjaxController::class, 'store'])->name('ajax.conversations.store');
    Route::get('/conversations/{conversation}/messages', [MessageAjaxController::class, 'index'])
        ->whereNumber('conversation')
        ->name('ajax.conversations.messages.index');
    Route::post('/conversations/{conversation}/messages', [MessageAjaxController::class, 'store'])
        ->whereNumber('conversation')
        ->name('ajax.conversations.messages.store');
    Route::post('/conversations/{conversation}/read', [ConversationReadAjaxController::class, 'update'])
        ->whereNumber('conversation')
        ->name('ajax.conversations.read');
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

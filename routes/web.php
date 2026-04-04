<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\AgentsWebController;
use App\Http\Controllers\Web\CryptoPocController;
use App\Http\Controllers\Web\MessagingWebController;
use App\Http\Controllers\Web\NotificationsWebController;
use App\Http\Controllers\Web\OperatorDashboardController;
use App\Http\Controllers\Web\OpsMonitoringHealthController;
use App\Http\Controllers\Web\SecurityWebController;
use App\Http\Controllers\Web\SettingsWebController;
use App\Http\Controllers\Web\WalletTransactionsController;
use App\Models\Agent;
use App\Models\AgentApiCredential;
use App\Models\AgentRun;
use App\Models\Workflow;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ops/monitor/health', [OpsMonitoringHealthController::class, 'show'])
    ->middleware(['ops.monitor', 'throttle:120,1'])
    ->name('ops.monitor.health');

Route::bind('agent', function (string $value): Agent {
    return Agent::query()
        ->whereKey($value)
        ->where('user_id', auth()->id())
        ->firstOrFail();
});

Route::bind('agent_api_credential', function (string $value): AgentApiCredential {
    return AgentApiCredential::query()
        ->whereKey($value)
        ->where('user_id', auth()->id())
        ->firstOrFail();
});

Route::bind('workflow', function (string $value): Workflow {
    return Workflow::query()
        ->whereKey($value)
        ->where('user_id', auth()->id())
        ->firstOrFail();
});

Route::bind('agent_run', function (string $value): AgentRun {
    return AgentRun::query()
        ->whereKey($value)
        ->where('user_id', auth()->id())
        ->firstOrFail();
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/agents', [AgentsWebController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('agents.index');

Route::get('/agents/{agent}/edit', [AgentsWebController::class, 'edit'])
    ->middleware(['auth', 'verified'])
    ->name('agents.edit');

Route::get('/crypto/poc', [CryptoPocController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('crypto.poc');

Route::get('/security', [SecurityWebController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('security.index');

Route::get('/settings', [SettingsWebController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('settings.index');

Route::get('/wallet/transactions', [WalletTransactionsController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('wallet.transactions');

Route::get('/notifications', [NotificationsWebController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('notifications.index');

Route::get('/messaging', [MessagingWebController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('messaging.index');

Route::get('/operator', [OperatorDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('operator.dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

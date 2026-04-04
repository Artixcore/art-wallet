<?php

use App\Jobs\PollTransactionStatusJob;
use App\Jobs\PruneExpiredDeviceChallengesJob;
use App\Jobs\PruneExpiredIntentsJob;
use App\Jobs\RunObservabilityProbesJob;
use App\Models\BlockchainTransaction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PruneExpiredDeviceChallengesJob)
    ->daily()
    ->name('artwallet-prune-device-challenges');

Schedule::job(new PruneExpiredIntentsJob)
    ->everyFiveMinutes()
    ->name('artwallet-prune-expired-intents');

Schedule::call(function (): void {
    BlockchainTransaction::query()
        ->where('status', BlockchainTransaction::STATUS_PENDING)
        ->orderBy('id')
        ->limit(50)
        ->pluck('id')
        ->each(fn (int $id) => PollTransactionStatusJob::dispatch($id));
})->everyMinute()->name('artwallet-poll-pending-transactions');

Schedule::job(new RunObservabilityProbesJob)
    ->everyTwoMinutes()
    ->name('artwallet-observability-probes');

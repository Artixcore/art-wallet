<?php

use App\Jobs\PruneExpiredDeviceChallengesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PruneExpiredDeviceChallengesJob)
    ->daily()
    ->name('artwallet-prune-device-challenges');

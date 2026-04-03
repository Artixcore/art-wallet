<?php

namespace App\Jobs;

use App\Models\DeviceChallenge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PruneExpiredDeviceChallengesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        DeviceChallenge::query()->where('expires_at', '<', now()->subDays(14))->delete();
    }
}

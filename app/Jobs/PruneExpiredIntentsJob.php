<?php

namespace App\Jobs;

use App\Models\TransactionIntent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PruneExpiredIntentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        TransactionIntent::query()
            ->where('status', TransactionIntent::STATUS_AWAITING_SIGNATURE)
            ->where('expires_at', '<', now())
            ->update(['status' => TransactionIntent::STATUS_CANCELLED]);
    }
}

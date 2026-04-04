<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Observability\Services\SystemHealthProbeRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RunObservabilityProbesJob implements ShouldQueue
{
    use Queueable;

    public function handle(SystemHealthProbeRunner $runner): void
    {
        $runner->runAll();
    }
}

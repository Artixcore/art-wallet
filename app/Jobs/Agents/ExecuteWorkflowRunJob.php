<?php

namespace App\Jobs\Agents;

use App\Domain\Workflows\Services\WorkflowRunService;
use App\Models\WorkflowRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * MVP: marks all pending steps succeeded and completes the run.
 */
class ExecuteWorkflowRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public int $workflowRunId,
    ) {}

    public function handle(WorkflowRunService $workflowRunService): void
    {
        $run = WorkflowRun::query()->find($this->workflowRunId);
        if ($run === null) {
            return;
        }

        $workflowRunService->markStepsCompleted($run);
    }
}

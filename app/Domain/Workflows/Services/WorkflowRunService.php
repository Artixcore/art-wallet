<?php

namespace App\Domain\Workflows\Services;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowRunStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WorkflowRunService
{
    public function __construct(
        private readonly WorkflowDefinitionValidator $validator,
    ) {}

    /**
     * @return array{run: WorkflowRun, steps: list<WorkflowRunStep>}
     */
    public function startManualRun(User $user, Workflow $workflow): array
    {
        if ((int) $workflow->user_id !== (int) $user->id) {
            abort(403);
        }

        $definition = $workflow->definition_json;
        $this->validator->validate($definition);

        return DB::transaction(function () use ($user, $workflow, $definition): array {
            $corr = (string) Str::uuid();
            $run = WorkflowRun::query()->create([
                'workflow_id' => $workflow->id,
                'user_id' => $user->id,
                'status' => 'running',
                'trigger_type' => 'manual',
                'correlation_id' => $corr,
                'started_at' => now(),
            ]);

            $nodes = $definition['nodes'];
            $sequence = 0;
            $steps = [];
            foreach ($nodes as $node) {
                $steps[] = WorkflowRunStep::query()->create([
                    'workflow_run_id' => $run->id,
                    'node_id' => (string) $node['id'],
                    'status' => 'pending',
                    'sequence' => $sequence++,
                ]);
            }

            return ['run' => $run, 'steps' => $steps];
        });
    }

    public function markStepsCompleted(WorkflowRun $run): void
    {
        $run->steps()->update(['status' => 'succeeded']);
        $run->update([
            'status' => 'succeeded',
            'finished_at' => now(),
        ]);
    }

    public function markAwaitingApproval(WorkflowRun $run, string $token): void
    {
        $run->update([
            'status' => 'awaiting_approval',
            'approval_token' => $token,
        ]);
    }
}

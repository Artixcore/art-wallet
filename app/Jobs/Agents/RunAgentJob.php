<?php

namespace App\Jobs\Agents;

use App\Domain\Agents\Services\AgentRunExecutionService;
use App\Models\AgentRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public int $agentRunId,
    ) {}

    public function handle(AgentRunExecutionService $execution): void
    {
        $run = AgentRun::query()->with(['agent', 'user'])->find($this->agentRunId);
        if ($run === null) {
            return;
        }

        $user = $run->user;
        $agent = $run->agent;
        $input = (string) ($run->meta_json['user_message'] ?? $run->input_summary ?? '');

        $execution->executeChat($user, $agent, $run, $input);
    }
}

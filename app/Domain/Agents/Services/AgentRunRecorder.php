<?php

namespace App\Domain\Agents\Services;

use App\Domain\Agents\Enums\AgentRunOutcome;
use App\Domain\Agents\Enums\AgentRunStatus;
use App\Models\Agent;
use App\Models\AgentLog;
use App\Models\AgentRun;
use App\Models\User;
use Illuminate\Support\Str;

final class AgentRunRecorder
{
    /**
     * @param  array<string, mixed>|null  $metaJson
     */
    public function startQueued(User $user, Agent $agent, string $mode, ?string $inputSummary = null, ?array $metaJson = null): AgentRun
    {
        return AgentRun::query()->create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'credential_id' => null,
            'mode' => $mode,
            'status' => AgentRunStatus::Queued->value,
            'outcome' => null,
            'correlation_id' => (string) Str::uuid(),
            'input_summary' => $inputSummary,
            'meta_json' => $metaJson,
        ]);
    }

    public function markRunning(AgentRun $run): void
    {
        $run->update([
            'status' => AgentRunStatus::Running->value,
            'started_at' => now(),
        ]);
    }

    public function markSucceeded(
        AgentRun $run,
        ?int $credentialId,
        string $provider,
        ?string $model,
        int $latencyMs,
        ?array $usage,
        string $outputText,
        ?array $costEstimate = null,
    ): void {
        $run->update([
            'credential_id' => $credentialId,
            'status' => AgentRunStatus::Succeeded->value,
            'outcome' => AgentRunOutcome::Success->value,
            'provider' => $provider,
            'model' => $model,
            'latency_ms' => $latencyMs,
            'usage_json' => $usage,
            'output_text' => $outputText,
            'cost_estimate_json' => $costEstimate,
            'finished_at' => now(),
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    public function markFailed(AgentRun $run, string $code, string $message): void
    {
        $run->update([
            'status' => AgentRunStatus::Failed->value,
            'outcome' => AgentRunOutcome::Failed->value,
            'error_code' => $code,
            'error_message' => $message,
            'finished_at' => now(),
        ]);
    }

    public function log(User $user, ?Agent $agent, ?AgentRun $run, string $level, string $event, string $message, array $context = []): void
    {
        AgentLog::query()->create([
            'user_id' => $user->id,
            'agent_id' => $agent?->id,
            'agent_run_id' => $run?->id,
            'level' => $level,
            'event' => $event,
            'message' => $message,
            'context_json' => $context,
        ]);
    }
}

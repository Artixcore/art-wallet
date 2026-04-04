<?php

namespace App\Domain\Agents\Services;

use App\Domain\Providers\Contracts\LlmCompletionRequest;
use App\Models\Agent;
use App\Models\AgentRun;
use App\Models\User;

final class AgentRunExecutionService
{
    public function __construct(
        private readonly LlmProviderRegistry $registry,
        private readonly AgentCredentialVault $vault,
        private readonly ProviderRouterService $router,
        private readonly ProviderBenchmarkService $benchmarks,
        private readonly AgentRunRecorder $recorder,
    ) {}

    public function executeChat(User $user, Agent $agent, AgentRun $run, string $userMessage): void
    {
        $this->recorder->markRunning($run);

        $prompt = $agent->promptVersions()->orderByDesc('version')->first();
        $system = (string) ($prompt?->system_prompt ?? 'You are a helpful assistant.');

        $creds = $this->router->orderedHealthyFirst($agent);
        if ($creds === []) {
            $this->recorder->markFailed($run, 'no_credentials', 'No provider credentials bound to this agent.');
            $this->recorder->log($user, $agent, $run, 'error', 'run.no_credentials', 'No credentials');

            return;
        }

        $budget = $agent->budget_json ?? [];
        $timeout = (int) ($budget['timeout_seconds'] ?? 120);
        $maxTokens = (int) ($budget['max_tokens_per_run'] ?? 4096);

        $lastError = '';
        foreach ($creds as $cred) {
            $payload = $this->vault->decryptPayload($cred);
            $providerKey = $cred->provider;

            try {
                $llm = $this->registry->resolveLlm($providerKey);
            } catch (\InvalidArgumentException $e) {
                $lastError = $e->getMessage();

                continue;
            }

            $model = (string) ($cred->metadata_json['default_model'] ?? match ($providerKey) {
                'openai' => 'gpt-4o-mini',
                'stub' => 'stub',
                default => 'gpt-4o-mini',
            });

            $req = new LlmCompletionRequest(
                model: $model,
                messages: [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                temperature: 0.5,
                maxTokens: min($maxTokens, 8192),
                timeoutSeconds: min($timeout, 180),
            );

            $result = $llm->complete($req, $payload);

            $this->benchmarks->recordObservation($user, $providerKey, $model, [
                'latency_ms' => $result->latencyMs,
                'success' => $result->ok,
            ]);

            if ($result->ok) {
                $this->recorder->markSucceeded(
                    $run,
                    $cred->id,
                    $providerKey,
                    $model,
                    $result->latencyMs,
                    $result->usage,
                    $result->content,
                );
                $this->recorder->log($user, $agent, $run, 'info', 'run.completed', 'Run completed', [
                    'provider' => $providerKey,
                    'latency_ms' => $result->latencyMs,
                ]);

                return;
            }

            $lastError = (string) ($result->providerErrorMessage ?? 'provider_error');
        }

        $this->recorder->markFailed($run, 'all_providers_failed', $lastError !== '' ? $lastError : 'All providers failed.');
        $this->recorder->log($user, $agent, $run, 'error', 'run.failed', 'All providers failed');
    }
}

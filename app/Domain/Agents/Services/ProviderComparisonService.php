<?php

namespace App\Domain\Agents\Services;

use App\Domain\Providers\Contracts\LlmCompletionRequest;
use App\Models\Agent;
use App\Models\AgentApiCredential;
use App\Models\ProviderComparisonResult;
use App\Models\User;
use Illuminate\Support\Str;

final class ProviderComparisonService
{
    public function __construct(
        private readonly LlmProviderRegistry $registry,
        private readonly AgentCredentialVault $vault,
        private readonly ProviderBenchmarkService $benchmarks,
    ) {}

    /**
     * Run LLM completion for each credential in parallel batches up to maxConcurrency.
     *
     * @param  list<AgentApiCredential>  $credentials
     * @return array{comparison_id: int, winner: array<string, mixed>|null, candidates: list<array<string, mixed>>}
     */
    public function compareLlm(
        User $user,
        Agent $agent,
        array $credentials,
        string $userMessage,
        int $maxConcurrency = 2,
        int $budgetMaxCalls = 6,
    ): array {
        if (count($credentials) > $budgetMaxCalls) {
            $credentials = array_slice($credentials, 0, $budgetMaxCalls);
        }

        $prompt = $agent->promptVersions()->orderByDesc('version')->first();
        $system = (string) ($prompt?->system_prompt ?? 'You are a helpful assistant.');

        $candidates = [];
        $winner = null;
        $bestScore = -INF;

        $chunks = array_chunk($credentials, max(1, $maxConcurrency));
        foreach ($chunks as $chunk) {
            foreach ($chunk as $cred) {
                $payload = $this->vault->decryptPayload($cred);
                $providerKey = $cred->provider;
                $llm = $this->registry->resolveLlm($providerKey);

                $model = (string) ($cred->metadata_json['default_model'] ?? 'gpt-4o-mini');
                if ($providerKey === 'stub') {
                    $model = 'stub';
                }

                $req = new LlmCompletionRequest(
                    model: $model,
                    messages: [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    temperature: 0.4,
                    maxTokens: 512,
                    timeoutSeconds: 45,
                );

                $started = microtime(true);
                $result = $llm->complete($req, $payload);
                $latency = $result->latencyMs;

                $this->benchmarks->recordObservation($user, $providerKey, $model, [
                    'latency_ms' => $latency,
                    'success' => $result->ok,
                ]);

                $score = $this->scoreCandidate($result->ok, $latency, $result->usage);

                $candidates[] = [
                    'credential_id' => $cred->id,
                    'provider' => $providerKey,
                    'model' => $model,
                    'ok' => $result->ok,
                    'latency_ms' => $latency,
                    'content_preview' => substr($result->content, 0, 400),
                    'score' => $score,
                    'error' => $result->providerErrorMessage,
                ];

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $winner = end($candidates);
                }
            }
        }

        $corr = (string) Str::uuid();
        $row = ProviderComparisonResult::query()->create([
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'correlation_id' => $corr,
            'candidates_json' => $candidates,
            'scores_json' => ['best_score' => $bestScore],
            'winner_provider' => $winner['provider'] ?? null,
            'winner_model' => $winner['model'] ?? null,
            'meta_json' => ['max_concurrency' => $maxConcurrency],
        ]);

        return [
            'comparison_id' => $row->id,
            'winner' => $winner,
            'candidates' => $candidates,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $usage
     */
    private function scoreCandidate(bool $ok, int $latencyMs, ?array $usage): float
    {
        if (! $ok) {
            return -1e9;
        }
        $tokens = (int) data_get($usage, 'total_tokens', data_get($usage, 'completion_tokens', 0));
        $costHint = $tokens;

        return 1000.0 / (1.0 + $latencyMs / 1000.0) - $costHint * 0.001;
    }
}

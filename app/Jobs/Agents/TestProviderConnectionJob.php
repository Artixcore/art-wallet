<?php

namespace App\Jobs\Agents;

use App\Domain\Agents\Services\AgentCredentialVault;
use App\Domain\Agents\Services\LlmProviderRegistry;
use App\Domain\Providers\Contracts\LlmCompletionRequest;
use App\Infrastructure\Providers\Market\FinnhubMarketDataProvider;
use App\Models\AgentApiCredential;
use App\Models\ProviderHealthCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestProviderConnectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public int $credentialId,
    ) {}

    public function handle(AgentCredentialVault $vault, LlmProviderRegistry $registry): void
    {
        $cred = AgentApiCredential::query()->find($this->credentialId);
        if ($cred === null) {
            return;
        }

        $payload = $vault->decryptPayload($cred);
        $started = microtime(true);

        if (in_array($cred->provider, ['openai', 'anthropic', 'gemini', 'xai', 'stub'], true)) {
            $llm = $registry->resolveLlm($cred->provider === 'stub' ? 'stub' : ($cred->provider === 'openai' ? 'openai' : 'stub'));
            $model = $cred->provider === 'openai' ? 'gpt-4o-mini' : 'stub';
            $req = new LlmCompletionRequest(
                model: $model,
                messages: [
                    ['role' => 'user', 'content' => 'ping'],
                ],
                maxTokens: 8,
                timeoutSeconds: 30,
            );
            $result = $llm->complete($req, $payload);
            $latency = (int) round((microtime(true) - $started) * 1000);
            ProviderHealthCheck::query()->create([
                'credential_id' => $cred->id,
                'status' => $result->ok ? 'ok' : 'error',
                'latency_ms' => $result->latencyMs > 0 ? $result->latencyMs : $latency,
                'error_json' => $result->ok ? null : ['message' => $result->providerErrorMessage],
                'checked_at' => now(),
            ]);

            return;
        }

        if ($cred->provider === 'finnhub') {
            $finnhub = app(FinnhubMarketDataProvider::class);
            $r = $finnhub->fetchQuote('AAPL', $payload);
            $latency = (int) round((microtime(true) - $started) * 1000);
            ProviderHealthCheck::query()->create([
                'credential_id' => $cred->id,
                'status' => $r['ok'] ? 'ok' : 'error',
                'latency_ms' => $r['latency_ms'] ?? $latency,
                'error_json' => $r['ok'] ? null : ['message' => $r['error'] ?? 'error'],
                'checked_at' => now(),
            ]);
        }
    }
}

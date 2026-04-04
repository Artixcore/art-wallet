<?php

namespace App\Infrastructure\Providers\Llm;

use App\Domain\Providers\Contracts\LlmCompletionRequest;
use App\Domain\Providers\Contracts\LlmCompletionResult;
use App\Domain\Providers\Contracts\LlmProviderInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

final class OpenAiLlmProvider implements LlmProviderInterface
{
    public function getProviderKey(): string
    {
        return 'openai';
    }

    public function complete(LlmCompletionRequest $request, array $decryptedCredentialPayload): LlmCompletionResult
    {
        $apiKey = (string) ($decryptedCredentialPayload['api_key'] ?? '');
        if ($apiKey === '') {
            return new LlmCompletionResult(
                ok: false,
                content: '',
                finishReason: null,
                usage: null,
                latencyMs: 0,
                providerErrorCode: 'missing_api_key',
                providerErrorMessage: 'OpenAI API key missing in credential payload.',
            );
        }

        $started = microtime(true);
        try {
            $response = Http::withToken($apiKey)
                ->timeout($request->timeoutSeconds)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $request->model,
                    'messages' => $request->messages,
                    'temperature' => $request->temperature ?? 0.7,
                    'max_tokens' => $request->maxTokens,
                ]);
        } catch (Throwable $e) {
            return new LlmCompletionResult(
                ok: false,
                content: '',
                finishReason: null,
                usage: null,
                latencyMs: (int) round((microtime(true) - $started) * 1000),
                providerErrorCode: 'http_exception',
                providerErrorMessage: $e->getMessage(),
            );
        }

        $latencyMs = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            $json = $response->json();

            return new LlmCompletionResult(
                ok: false,
                content: '',
                finishReason: null,
                usage: null,
                latencyMs: $latencyMs,
                providerErrorCode: (string) ($json['error']['code'] ?? 'http_'.$response->status()),
                providerErrorMessage: (string) ($json['error']['message'] ?? $response->body()),
            );
        }

        $json = $response->json();
        $text = (string) data_get($json, 'choices.0.message.content', '');
        $finish = (string) data_get($json, 'choices.0.finish_reason', '');

        return new LlmCompletionResult(
            ok: true,
            content: $text,
            finishReason: $finish !== '' ? $finish : null,
            usage: is_array($json['usage'] ?? null) ? $json['usage'] : null,
            latencyMs: $latencyMs,
            providerErrorCode: null,
            providerErrorMessage: null,
            rawMeta: ['id' => data_get($json, 'id')],
        );
    }
}

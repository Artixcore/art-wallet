<?php

namespace App\Infrastructure\Providers\Llm;

use App\Domain\Providers\Contracts\LlmCompletionRequest;
use App\Domain\Providers\Contracts\LlmCompletionResult;
use App\Domain\Providers\Contracts\LlmProviderInterface;

final class StubLlmProvider implements LlmProviderInterface
{
    public function getProviderKey(): string
    {
        return 'stub';
    }

    public function complete(LlmCompletionRequest $request, array $decryptedCredentialPayload): LlmCompletionResult
    {
        $started = microtime(true);
        $last = end($request->messages);
        $user = is_array($last) ? (string) ($last['content'] ?? '') : '';

        return new LlmCompletionResult(
            ok: true,
            content: '[stub] '.substr($user, 0, 2000),
            finishReason: 'stop',
            usage: ['prompt_tokens' => 10, 'completion_tokens' => 20],
            latencyMs: (int) round((microtime(true) - $started) * 1000),
            providerErrorCode: null,
            providerErrorMessage: null,
            rawMeta: ['stub' => true],
        );
    }
}

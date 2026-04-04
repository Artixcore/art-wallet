<?php

namespace App\Domain\Providers\Contracts;

interface LlmProviderInterface
{
    public function getProviderKey(): string;

    /**
     * @param  array<string, mixed>  $decryptedCredentialPayload  key material / config from DB
     */
    public function complete(LlmCompletionRequest $request, array $decryptedCredentialPayload): LlmCompletionResult;
}

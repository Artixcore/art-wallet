<?php

namespace App\Domain\Agents\Services;

use App\Domain\Providers\Contracts\LlmProviderInterface;
use App\Infrastructure\Providers\Llm\OpenAiLlmProvider;
use App\Infrastructure\Providers\Llm\StubLlmProvider;
use InvalidArgumentException;

final class LlmProviderRegistry
{
    /**
     * @var array<string, LlmProviderInterface>
     */
    private array $providers;

    public function __construct(
        StubLlmProvider $stub,
        OpenAiLlmProvider $openAi,
    ) {
        $this->providers = [
            $stub->getProviderKey() => $stub,
            $openAi->getProviderKey() => $openAi,
        ];
    }

    public function get(string $providerKey): LlmProviderInterface
    {
        if (! isset($this->providers[$providerKey])) {
            throw new InvalidArgumentException('Unknown LLM provider: '.$providerKey);
        }

        return $this->providers[$providerKey];
    }

    /**
     * @return list<string>
     */
    public function registeredKeys(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Resolve an LLM implementation; unknown keys fall back to stub for safe degradation.
     */
    public function resolveLlm(string $providerKey): LlmProviderInterface
    {
        if (isset($this->providers[$providerKey])) {
            return $this->providers[$providerKey];
        }

        // Not yet implemented providers: use stub until adapters ship.
        return $this->providers['stub'];
    }
}

<?php

namespace App\Domain\Providers\Contracts;

final readonly class LlmCompletionRequest
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function __construct(
        public string $model,
        public array $messages,
        public ?float $temperature = null,
        public int $maxTokens = 1024,
        public int $timeoutSeconds = 60,
    ) {}
}

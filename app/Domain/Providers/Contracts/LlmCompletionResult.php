<?php

namespace App\Domain\Providers\Contracts;

final readonly class LlmCompletionResult
{
    /**
     * @param  array<string, mixed>|null  $usage
     * @param  array<string, mixed>  $rawMeta
     */
    public function __construct(
        public bool $ok,
        public string $content,
        public ?string $finishReason,
        public ?array $usage,
        public int $latencyMs,
        public ?string $providerErrorCode,
        public ?string $providerErrorMessage,
        public array $rawMeta = [],
    ) {}
}

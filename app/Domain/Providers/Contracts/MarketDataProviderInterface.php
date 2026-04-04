<?php

namespace App\Domain\Providers\Contracts;

interface MarketDataProviderInterface
{
    public function getProviderKey(): string;

    /**
     * @param  array<string, mixed>  $decryptedCredentialPayload
     * @return array{ok: bool, data: array<string, mixed>, latency_ms: int, error?: string}
     */
    public function fetchQuote(string $symbol, array $decryptedCredentialPayload): array;
}

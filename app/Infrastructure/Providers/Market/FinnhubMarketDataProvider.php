<?php

namespace App\Infrastructure\Providers\Market;

use App\Domain\Providers\Contracts\MarketDataProviderInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

final class FinnhubMarketDataProvider implements MarketDataProviderInterface
{
    public function getProviderKey(): string
    {
        return 'finnhub';
    }

    public function fetchQuote(string $symbol, array $decryptedCredentialPayload): array
    {
        $token = (string) ($decryptedCredentialPayload['api_key'] ?? '');
        if ($token === '') {
            return ['ok' => false, 'data' => [], 'latency_ms' => 0, 'error' => 'missing_api_key'];
        }

        $started = microtime(true);
        try {
            $response = Http::timeout(15)
                ->get('https://finnhub.io/api/v1/quote', [
                    'symbol' => $symbol,
                    'token' => $token,
                ]);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'data' => [],
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'error' => $e->getMessage(),
            ];
        }

        $latency = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            return ['ok' => false, 'data' => [], 'latency_ms' => $latency, 'error' => 'http_'.$response->status()];
        }

        return ['ok' => true, 'data' => $response->json() ?: [], 'latency_ms' => $latency];
    }
}

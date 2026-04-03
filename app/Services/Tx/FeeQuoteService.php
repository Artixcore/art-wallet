<?php

declare(strict_types=1);

namespace App\Services\Tx;

use App\Domain\Chain\ChainAdapterResolver;
use App\Models\Asset;
use App\Models\FeeEstimate;
use App\Models\SupportedNetwork;

final class FeeQuoteService
{
    public function __construct(
        private readonly ChainAdapterResolver $adapters,
    ) {}

    /**
     * @return array{tiers: array<string, mixed>, expires_at: int, cached: bool}
     */
    public function quote(SupportedNetwork $network, ?Asset $asset = null): array
    {
        $tier = 'standard';
        $existing = FeeEstimate::query()
            ->where('supported_network_id', $network->id)
            ->where('tier', $tier)
            ->where('expires_at', '>', now())
            ->first();
        if ($existing !== null) {
            return [
                'tiers' => $existing->value_json,
                'expires_at' => $existing->expires_at->getTimestamp(),
                'cached' => true,
            ];
        }

        $adapter = $this->adapters->forNetwork($network);
        $result = $adapter->estimateFees($network, $asset);
        FeeEstimate::query()->create([
            'supported_network_id' => $network->id,
            'tier' => $tier,
            'value_json' => $result->tiers,
            'expires_at' => \Carbon\Carbon::createFromTimestamp($result->expiresAtEpoch),
        ]);

        return [
            'tiers' => $result->tiers,
            'expires_at' => $result->expiresAtEpoch,
            'cached' => false,
        ];
    }
}

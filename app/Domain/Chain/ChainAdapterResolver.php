<?php

declare(strict_types=1);

namespace App\Domain\Chain;

use App\Domain\Chain\Contracts\ChainAdapterInterface;
use App\Domain\Chain\Exceptions\ChainAdapterException;
use App\Models\SupportedNetwork;

final class ChainAdapterResolver
{
    /**
     * @param  iterable<int, ChainAdapterInterface>  $adapters
     */
    public function __construct(
        private readonly iterable $adapters,
    ) {}

    public function forNetwork(SupportedNetwork $network): ChainAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($network)) {
                return $adapter;
            }
        }

        throw new ChainAdapterException('No chain adapter for network: '.$network->slug);
    }
}

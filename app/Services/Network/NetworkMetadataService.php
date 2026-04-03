<?php

declare(strict_types=1);

namespace App\Services\Network;

use App\Models\Asset;
use App\Models\SupportedNetwork;
use Illuminate\Support\Collection;

final class NetworkMetadataService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function enabledNetworksWithAssets(): Collection
    {
        return SupportedNetwork::query()
            ->where('enabled', true)
            ->with(['assets' => fn ($q) => $q->where('enabled', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SupportedNetwork $n) => [
                'id' => $n->id,
                'slug' => $n->slug,
                'chain' => $n->chain,
                'display_name' => $n->display_name,
                'chain_id' => $n->chain_id,
                'is_testnet' => $n->is_testnet,
                'explorer_tx_url_template' => $n->explorer_tx_url_template,
                'assets' => $n->assets->map(fn (Asset $a) => [
                    'id' => $a->id,
                    'code' => $a->code,
                    'network' => $a->network,
                    'asset_type' => $a->asset_type,
                    'decimals' => $a->decimals,
                    'contract_address' => $a->contract_address,
                    'display_label' => $a->displayLabel(),
                ])->values()->all(),
            ]);
    }
}

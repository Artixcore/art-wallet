<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\SupportedNetwork;
use App\Services\Tx\FeeQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeeEstimateAjaxController extends Controller
{
    public function show(Request $request, FeeQuoteService $fees): JsonResponse
    {
        $data = $request->validate([
            'supported_network_id' => ['required', 'integer', 'exists:supported_networks,id'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
        ]);
        $network = SupportedNetwork::query()->whereKey($data['supported_network_id'])->firstOrFail();
        $asset = isset($data['asset_id'])
            ? Asset::query()->whereKey($data['asset_id'])->where('supported_network_id', $network->id)->firstOrFail()
            : null;

        $quote = $fees->quote($network, $asset);

        return response()->json([
            'supported_network_id' => $network->id,
            'tiers' => $quote['tiers'],
            'expires_at' => $quote['expires_at'],
            'cached' => $quote['cached'],
        ]);
    }
}

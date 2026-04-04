<?php

namespace App\Http\Controllers\Api;

use App\Domain\Chain\ChainAdapterResolver;
use App\Domain\Messaging\Services\VerifiedWalletAddressSyncService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\SyncWalletAddressesRequest;
use App\Models\SupportedNetwork;
use App\Models\Wallet;
use App\Models\WalletAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WalletAddressAjaxController extends Controller
{
    public function sync(SyncWalletAddressesRequest $request, Wallet $wallet, ChainAdapterResolver $adapters, VerifiedWalletAddressSyncService $verifiedSol): JsonResponse
    {
        $this->authorize('manageAddresses', $wallet);

        $payload = $request->validated();
        $addresses = $payload['addresses'];

        $normalizedRows = [];
        foreach ($addresses as $row) {
            $network = SupportedNetwork::query()->whereKey($row['supported_network_id'])->firstOrFail();
            $adapter = $adapters->forNetwork($network);
            if (! $adapter->validateAddress($row['address'], $network)) {
                return response()->json(['message' => 'Invalid address for network '.$network->slug], 422);
            }
            $normalizedRows[] = [$network, $adapter->normalizeAddress($row['address'], $network), $row];
        }

        DB::transaction(function () use ($wallet, $normalizedRows) {
            foreach ($normalizedRows as [$network, $normalized, $row]) {
                WalletAddress::query()->updateOrCreate(
                    [
                        'wallet_id' => $wallet->id,
                        'chain' => $network->chain,
                        'address' => $normalized,
                    ],
                    [
                        'supported_network_id' => $network->id,
                        'derivation_path' => $row['derivation_path'] ?? null,
                        'derivation_index' => $row['derivation_index'] ?? 0,
                        'is_change' => $row['is_change'] ?? false,
                    ]
                );
            }
        });

        foreach ($normalizedRows as [$network, $normalized]) {
            if ($network->chain === 'SOL') {
                $verifiedSol->syncSolAddressFromWallet($wallet, $normalized);
            }
        }

        $synced = $wallet->walletAddresses()->with('supportedNetwork')->get();

        return response()->json(['addresses' => $synced]);
    }
}

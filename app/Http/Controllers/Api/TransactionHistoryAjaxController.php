<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockchainTransaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionHistoryAjaxController extends Controller
{
    public function index(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);

        $rows = BlockchainTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->with(['supportedNetwork', 'asset'])
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (BlockchainTransaction $t) => [
                'id' => $t->id,
                'txid' => $t->txid,
                'direction' => $t->direction,
                'status' => $t->status,
                'confirmations' => $t->confirmations,
                'counterparty_address' => $t->counterparty_address,
                'amount_atomic' => $t->amount_atomic !== null ? (string) $t->amount_atomic : null,
                'network_slug' => $t->supportedNetwork?->slug,
                'asset_code' => $t->asset?->code,
                'explorer_url' => $t->supportedNetwork?->explorerUrlForTxid($t->txid),
                'created_at' => $t->created_at?->toIso8601String(),
            ]);

        return response()->json(['transactions' => $rows]);
    }

    public function show(Request $request, Wallet $wallet, BlockchainTransaction $blockchainTransaction): JsonResponse
    {
        $this->authorize('view', $wallet);
        if ((int) $blockchainTransaction->wallet_id !== (int) $wallet->id) {
            abort(404);
        }
        $blockchainTransaction->load(['supportedNetwork', 'asset', 'statusHistories']);

        return response()->json([
            'transaction' => [
                'id' => $blockchainTransaction->id,
                'txid' => $blockchainTransaction->txid,
                'direction' => $blockchainTransaction->direction,
                'status' => $blockchainTransaction->status,
                'confirmations' => $blockchainTransaction->confirmations,
                'block_height' => $blockchainTransaction->block_height,
                'counterparty_address' => $blockchainTransaction->counterparty_address,
                'amount_atomic' => $blockchainTransaction->amount_atomic !== null ? (string) $blockchainTransaction->amount_atomic : null,
                'raw_metadata_json' => $blockchainTransaction->raw_metadata_json,
                'network' => $blockchainTransaction->supportedNetwork,
                'asset' => $blockchainTransaction->asset,
                'history' => $blockchainTransaction->statusHistories,
                'explorer_url' => $blockchainTransaction->supportedNetwork?->explorerUrlForTxid($blockchainTransaction->txid),
            ],
        ]);
    }
}

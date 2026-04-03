<?php

namespace App\Http\Controllers\Api;

use App\Domain\Chain\Exceptions\BroadcastRejectedException;
use App\Domain\Chain\Exceptions\ChainAdapterException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\BroadcastTransactionRequest;
use App\Models\TransactionIntent;
use App\Models\Wallet;
use App\Services\Tx\BroadcastService;
use Illuminate\Http\JsonResponse;

class BroadcastAjaxController extends Controller
{
    public function store(
        BroadcastTransactionRequest $request,
        Wallet $wallet,
        TransactionIntent $intent,
        BroadcastService $broadcast,
    ): JsonResponse {
        $this->authorize('createTransactionIntent', $wallet);
        if ((int) $intent->wallet_id !== (int) $wallet->id) {
            abort(404);
        }
        $this->authorize('broadcast', $intent);

        $row = $request->validated();
        $idem = $request->header('Idempotency-Key') ?? $row['idempotency_key'] ?? null;

        try {
            $result = $broadcast->broadcast(
                $request->user(),
                $intent,
                $row['server_nonce'],
                $row['signed_tx_hex'],
                $idem,
            );
        } catch (BroadcastRejectedException|ChainAdapterException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $intent = $result['intent'];
        $intent->load('supportedNetwork');

        return response()->json([
            'txid' => $result['txid'],
            'explorer_url' => $intent->supportedNetwork->explorerUrlForTxid($result['txid']),
            'intent_status' => $intent->status,
        ]);
    }
}

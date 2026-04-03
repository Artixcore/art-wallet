<?php

namespace App\Http\Controllers\Api;

use App\Domain\Chain\Exceptions\ChainAdapterException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreTransactionIntentRequest;
use App\Models\TransactionIntent;
use App\Models\Wallet;
use App\Services\Tx\TransactionIntentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionIntentAjaxController extends Controller
{
    public function store(StoreTransactionIntentRequest $request, Wallet $wallet, TransactionIntentService $intents): JsonResponse
    {
        $this->authorize('createTransactionIntent', $wallet);

        $row = $request->validated();
        try {
            $created = $intents->createOutgoingIntent($wallet, $row);
        } catch (ChainAdapterException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $intent = $created['intent'];
        $signing = $created['signing_request'];
        $intent->load(['asset.supportedNetwork', 'supportedNetwork']);

        return response()->json([
            'intent' => [
                'id' => $intent->id,
                'intent_hash' => $intent->intent_hash,
                'status' => $intent->status,
                'expires_at' => $intent->expires_at->toIso8601String(),
                'from_address' => $intent->from_address,
                'to_address' => $intent->to_address,
                'amount_atomic' => (string) $intent->amount_atomic,
                'memo' => $intent->memo,
                'asset' => [
                    'id' => $intent->asset->id,
                    'code' => $intent->asset->code,
                    'asset_type' => $intent->asset->asset_type,
                    'decimals' => $intent->asset->decimals,
                    'contract_address' => $intent->asset->contract_address,
                ],
                'network' => [
                    'id' => $intent->supportedNetwork->id,
                    'slug' => $intent->supportedNetwork->slug,
                    'chain' => $intent->supportedNetwork->chain,
                    'chain_id' => $intent->supportedNetwork->chain_id,
                ],
                'construction_payload' => $intent->construction_payload_json,
            ],
            'signing_request' => [
                'server_nonce' => $signing->server_nonce,
                'expires_at' => $signing->expires_at->toIso8601String(),
            ],
        ], 201);
    }

    public function show(Request $request, Wallet $wallet, TransactionIntent $intent): JsonResponse
    {
        $this->authorize('createTransactionIntent', $wallet);
        if ((int) $intent->wallet_id !== (int) $wallet->id) {
            abort(404);
        }
        $this->authorize('view', $intent);
        $intent->load(['asset', 'supportedNetwork']);

        return response()->json([
            'intent' => [
                'id' => $intent->id,
                'intent_hash' => $intent->intent_hash,
                'status' => $intent->status,
                'expires_at' => $intent->expires_at->toIso8601String(),
                'from_address' => $intent->from_address,
                'to_address' => $intent->to_address,
                'amount_atomic' => (string) $intent->amount_atomic,
                'construction_payload' => $intent->construction_payload_json,
                'asset' => $intent->asset,
                'network' => $intent->supportedNetwork,
            ],
        ]);
    }
}

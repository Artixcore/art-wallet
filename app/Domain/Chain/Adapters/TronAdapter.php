<?php

declare(strict_types=1);

namespace App\Domain\Chain\Adapters;

use App\Domain\Chain\Contracts\ChainAdapterInterface;
use App\Domain\Chain\DTO\BroadcastResult;
use App\Domain\Chain\DTO\FeeEstimateResult;
use App\Domain\Chain\Exceptions\BroadcastRejectedException;
use App\Domain\Chain\Exceptions\ChainAdapterException;
use App\Domain\Chain\Exceptions\TamperedTransactionException;
use App\Models\Asset;
use App\Models\SupportedNetwork;
use App\Models\TransactionIntent;

/**
 * Tron wire format differs from raw-hex EVM-style payloads; broadcast via TronGrid
 * expects protobuf-signed JSON. This adapter validates addresses and surfaces fees only.
 */
final class TronAdapter implements ChainAdapterInterface
{
    public function supports(SupportedNetwork $network): bool
    {
        return $network->chain === 'TRON';
    }

    public function validateAddress(string $address, SupportedNetwork $network): bool
    {
        if (strlen($address) < 30 || strlen($address) > 50) {
            return false;
        }

        return str_starts_with($address, 'T');
    }

    public function normalizeAddress(string $address, SupportedNetwork $network): string
    {
        return $address;
    }

    public function buildConstructionPayload(TransactionIntent $intent, Asset $asset): array
    {
        return [
            'note' => 'Tron signing requires client protobuf + TronGrid broadcast; use dedicated Tron tooling.',
        ];
    }

    public function estimateFees(SupportedNetwork $network, ?Asset $asset = null): FeeEstimateResult
    {
        $ttl = (int) config('artwallet_chains.fee_estimate_ttl_seconds', 60);

        return new FeeEstimateResult(
            [
                'slow' => ['bandwidth_note' => 'Pay TRX for bandwidth/energy on TronGrid'],
                'standard' => [],
                'fast' => [],
            ],
            time() + $ttl,
        );
    }

    public function broadcastSignedTransaction(
        SupportedNetwork $network,
        string $rawSignedHex,
        TransactionIntent $intent,
    ): BroadcastResult {
        throw new BroadcastRejectedException(
            'Tron broadcast is not enabled for unified hex payloads. Integrate TronWeb protobuf signing and /wallet/broadcasttransaction separately.'
        );
    }

    public function fetchTransactionStatus(SupportedNetwork $network, string $txid): array
    {
        throw new ChainAdapterException('Tron transaction status polling is not wired in this adapter.');
    }

    public function assertSignedTxMatchesIntent(
        TransactionIntent $intent,
        Asset $asset,
        string $rawSignedHex,
    ): void {
        throw new TamperedTransactionException(
            'Tron transactions cannot be verified with the unified hex pipeline. Do not broadcast Tron from this endpoint until the Tron adapter is completed.'
        );
    }
}

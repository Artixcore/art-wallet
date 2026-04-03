<?php

declare(strict_types=1);

namespace App\Domain\Chain\Contracts;

use App\Domain\Chain\DTO\BroadcastResult;
use App\Domain\Chain\DTO\FeeEstimateResult;
use App\Models\Asset;
use App\Models\SupportedNetwork;
use App\Models\TransactionIntent;

interface ChainAdapterInterface
{
    public function supports(SupportedNetwork $network): bool;

    public function validateAddress(string $address, SupportedNetwork $network): bool;

    public function normalizeAddress(string $address, SupportedNetwork $network): string;

    /**
     * @return array<string, mixed> Construction hints for client signing (nonces, gas, blockhash, etc.)
     */
    public function buildConstructionPayload(TransactionIntent $intent, Asset $asset): array;

    public function estimateFees(SupportedNetwork $network, ?Asset $asset = null): FeeEstimateResult;

    public function broadcastSignedTransaction(
        SupportedNetwork $network,
        string $rawSignedHex,
        TransactionIntent $intent,
    ): BroadcastResult;

    /**
     * @return array{txid: string, confirmations: int, status: string, block_height: ?int, raw: array<string, mixed>}
     */
    public function fetchTransactionStatus(SupportedNetwork $network, string $txid): array;

    /**
     * Verify parsed signed transaction matches intent (chain-specific).
     *
     * @throws \App\Domain\Chain\Exceptions\TamperedTransactionException
     */
    public function assertSignedTxMatchesIntent(
        TransactionIntent $intent,
        Asset $asset,
        string $rawSignedHex,
    ): void;
}

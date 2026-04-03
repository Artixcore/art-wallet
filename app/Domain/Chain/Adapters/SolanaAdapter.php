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
use Illuminate\Support\Facades\Http;

final class SolanaAdapter implements ChainAdapterInterface
{
    public function supports(SupportedNetwork $network): bool
    {
        return $network->chain === 'SOL';
    }

    public function validateAddress(string $address, SupportedNetwork $network): bool
    {
        if (strlen($address) < 32 || strlen($address) > 50) {
            return false;
        }

        return (bool) preg_match('/^[1-9A-HJ-NP-Za-km-z]+$/', $address);
    }

    public function normalizeAddress(string $address, SupportedNetwork $network): string
    {
        return $address;
    }

    public function buildConstructionPayload(TransactionIntent $intent, Asset $asset): array
    {
        $rpc = config('artwallet_chains.solana_rpc_url');
        $blockhash = $this->rpc($rpc, 'getLatestBlockhash', [['commitment' => 'finalized']]);
        $bh = $blockhash['value']['blockhash'] ?? null;
        if (! is_string($bh)) {
            throw new ChainAdapterException('Could not load Solana blockhash.');
        }

        return [
            'recent_blockhash' => $bh,
            'last_valid_block_height' => $blockhash['value']['lastValidBlockHeight'] ?? null,
        ];
    }

    public function estimateFees(SupportedNetwork $network, ?Asset $asset = null): FeeEstimateResult
    {
        $ttl = (int) config('artwallet_chains.fee_estimate_ttl_seconds', 60);

        return new FeeEstimateResult(
            [
                'slow' => ['priority_micro_lamports' => 0],
                'standard' => ['priority_micro_lamports' => 1000],
                'fast' => ['priority_micro_lamports' => 10_000],
            ],
            time() + $ttl,
        );
    }

    public function broadcastSignedTransaction(
        SupportedNetwork $network,
        string $rawSignedHex,
        TransactionIntent $intent,
    ): BroadcastResult {
        $rpc = config('artwallet_chains.solana_rpc_url');
        $hex = strtolower($rawSignedHex);
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }
        $binary = @hex2bin($hex);
        if ($binary === false) {
            throw new BroadcastRejectedException('Invalid Solana transaction hex.');
        }
        $b64 = base64_encode($binary);
        $sig = $this->rpc($rpc, 'sendTransaction', [
            $b64,
            ['encoding' => 'base64', 'skipPreflight' => false],
        ]);
        if (! is_string($sig)) {
            throw new BroadcastRejectedException('Solana RPC returned no signature.');
        }

        return new BroadcastResult($sig);
    }

    public function fetchTransactionStatus(SupportedNetwork $network, string $txid): array
    {
        $rpc = config('artwallet_chains.solana_rpc_url');
        $statuses = $this->rpc($rpc, 'getSignatureStatuses', [[$txid], ['searchTransactionHistory' => true]]);
        $st = $statuses['value'][0] ?? null;
        if (! is_array($st)) {
            return [
                'txid' => $txid,
                'confirmations' => 0,
                'status' => TransactionIntent::STATUS_PENDING,
                'block_height' => null,
                'raw' => [],
            ];
        }
        $err = $st['err'] ?? null;
        if ($err !== null) {
            return [
                'txid' => $txid,
                'confirmations' => 0,
                'status' => TransactionIntent::STATUS_FAILED,
                'block_height' => null,
                'raw' => $st,
            ];
        }
        $confirmed = isset($st['confirmationStatus']) && in_array($st['confirmationStatus'], ['confirmed', 'finalized'], true);

        return [
            'txid' => $txid,
            'confirmations' => $confirmed ? 32 : 0,
            'status' => $confirmed ? TransactionIntent::STATUS_CONFIRMED : TransactionIntent::STATUS_PENDING,
            'block_height' => null,
            'raw' => $st,
        ];
    }

    public function assertSignedTxMatchesIntent(
        TransactionIntent $intent,
        Asset $asset,
        string $rawSignedHex,
    ): void {
        if ($asset->asset_type !== 'native') {
            throw new TamperedTransactionException('Solana SPL token sends are not verified server-side in this build.');
        }
        $hex = strtolower($rawSignedHex);
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }
        $len = strlen($hex);
        if ($len < 128 || $len % 2 !== 0 || $len > 2_000_000) {
            throw new TamperedTransactionException('Invalid Solana signed transaction encoding.');
        }
    }

    /**
     * @param  list<mixed>  $params
     */
    private function rpc(string $url, string $method, array $params): mixed
    {
        $response = Http::timeout(25)->acceptJson()->post($url, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ]);
        if (! $response->successful()) {
            throw new ChainAdapterException('Solana RPC HTTP error: '.$response->status());
        }
        $json = $response->json();
        if (isset($json['error'])) {
            $msg = is_array($json['error']) ? ($json['error']['message'] ?? json_encode($json['error'])) : (string) $json['error'];
            throw new ChainAdapterException($msg);
        }

        return $json['result'] ?? null;
    }
}

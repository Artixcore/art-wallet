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
use App\Services\Tx\Erc20TransferEncoder;
use App\Services\Tx\EvmSignedTransactionDecoder;
use Illuminate\Support\Facades\Http;

final class EthereumAdapter implements ChainAdapterInterface
{
    public function __construct(
        private readonly EvmSignedTransactionDecoder $decoder,
        private readonly Erc20TransferEncoder $erc20Encoder,
    ) {}

    public function supports(SupportedNetwork $network): bool
    {
        return $network->chain === 'ETH' && $network->chain_id !== null;
    }

    public function validateAddress(string $address, SupportedNetwork $network): bool
    {
        if (! str_starts_with($address, '0x') || strlen($address) !== 42) {
            return (bool) preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
        }

        return (bool) preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }

    public function normalizeAddress(string $address, SupportedNetwork $network): string
    {
        return strtolower($address);
    }

    public function buildConstructionPayload(TransactionIntent $intent, Asset $asset): array
    {
        $rpc = config('artwallet_chains.ethereum_rpc_url');
        $from = strtolower($intent->from_address);
        $nonceHex = $this->rpc($rpc, 'eth_getTransactionCount', [$from, 'latest'], false);
        $nonce = hexdec((string) $nonceHex);
        $block = $this->rpc($rpc, 'eth_getBlockByNumber', ['latest', false], false);
        $baseFee = isset($block['baseFeePerGas']) ? hexdec($block['baseFeePerGas']) : 0;
        $priority = (int) max(1, (int) ($baseFee * 0.1) + 1);
        $maxFee = (int) ($baseFee * 2 + $priority);

        return [
            'chain_id' => (int) $intent->supportedNetwork->chain_id,
            'nonce' => $nonce,
            'max_priority_fee_per_gas' => $priority,
            'max_fee_per_gas' => max($maxFee, $priority + 1),
            'gas' => $asset->asset_type === 'native' ? 21_000 : 100_000,
        ];
    }

    public function estimateFees(SupportedNetwork $network, ?Asset $asset = null): FeeEstimateResult
    {
        $rpc = config('artwallet_chains.ethereum_rpc_url');
        $slow = 1;
        $std = 2;
        $fast = 3;
        try {
            $history = $this->rpc($rpc, 'eth_feeHistory', ['0x5', 'latest', [10, 50, 90]], false);
            if (is_array($history) && isset($history['reward']) && is_array($history['reward'])) {
                $reward = $history['reward'];
                $last = end($reward) ?: ['0x0', '0x0', '0x0'];
                $slow = max(1, hexdec($last[0] ?? '0x0'));
                $std = max(1, hexdec($last[1] ?? '0x0'));
                $fast = max(1, hexdec($last[2] ?? '0x0'));
            }
        } catch (\Throwable) {
            // fall through with defaults
        }
        $ttl = (int) config('artwallet_chains.fee_estimate_ttl_seconds', 60);

        return new FeeEstimateResult(
            [
                'slow' => ['max_priority_fee_per_gas' => $slow],
                'standard' => ['max_priority_fee_per_gas' => $std],
                'fast' => ['max_priority_fee_per_gas' => $fast],
            ],
            time() + $ttl,
        );
    }

    public function broadcastSignedTransaction(
        SupportedNetwork $network,
        string $rawSignedHex,
        TransactionIntent $intent,
    ): BroadcastResult {
        $rpc = config('artwallet_chains.ethereum_rpc_url');
        if (! str_starts_with($rawSignedHex, '0x')) {
            $rawSignedHex = '0x'.$rawSignedHex;
        }
        try {
            $txid = $this->rpc($rpc, 'eth_sendRawTransaction', [$rawSignedHex], true);
        } catch (\Throwable $e) {
            throw new BroadcastRejectedException('Broadcast failed: '.$e->getMessage(), previous: $e);
        }

        return new BroadcastResult($txid);
    }

    public function fetchTransactionStatus(SupportedNetwork $network, string $txid): array
    {
        $rpc = config('artwallet_chains.ethereum_rpc_url');
        $receipt = $this->rpc($rpc, 'eth_getTransactionReceipt', [$txid], false);
        if ($receipt === null) {
            return [
                'txid' => $txid,
                'confirmations' => 0,
                'status' => TransactionIntent::STATUS_PENDING,
                'block_height' => null,
                'raw' => [],
            ];
        }
        $blockNumber = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null;
        $statusOk = isset($receipt['status']) && $receipt['status'] === '0x1';

        return [
            'txid' => $txid,
            'confirmations' => $blockNumber !== null ? 1 : 0,
            'status' => $statusOk ? TransactionIntent::STATUS_CONFIRMED : TransactionIntent::STATUS_FAILED,
            'block_height' => $blockNumber,
            'raw' => $receipt,
        ];
    }

    public function assertSignedTxMatchesIntent(
        TransactionIntent $intent,
        Asset $asset,
        string $rawSignedHex,
    ): void {
        $parsed = $this->decoder->decodeType2($rawSignedHex);
        $expectedChain = (string) $intent->supportedNetwork->chain_id;
        if ($parsed['chainId'] !== $expectedChain) {
            throw new TamperedTransactionException('Signed transaction chain id does not match intent network.');
        }
        if ($asset->asset_type === 'native') {
            $expectedTo = $this->normalizeAddress($intent->to_address, $intent->supportedNetwork);
            if (strtolower((string) $parsed['to']) !== strtolower($expectedTo)) {
                throw new TamperedTransactionException('Recipient does not match intent.');
            }
            if ($this->hexWeiToDecimal($parsed['value']) !== (string) $intent->amount_atomic) {
                throw new TamperedTransactionException('Value does not match intent amount.');
            }
            if ($parsed['data'] !== '' && $parsed['data'] !== '0x') {
                throw new TamperedTransactionException('Native transfer must not carry calldata.');
            }
        } elseif ($asset->asset_type === 'erc20') {
            $contract = strtolower((string) $asset->contract_address);
            if (strtolower((string) $parsed['to']) !== $contract) {
                throw new TamperedTransactionException('ERC-20 contract does not match allowlisted asset.');
            }
            if ($parsed['value'] !== '0') {
                throw new TamperedTransactionException('ERC-20 transfer must send zero ETH.');
            }
            $expectedData = strtolower(substr($this->erc20Encoder->encode($intent->to_address, (string) $intent->amount_atomic), 2));
            $actualData = strtolower($parsed['data']);
            if ($actualData !== $expectedData) {
                throw new TamperedTransactionException('ERC-20 calldata does not match intent.');
            }
        } else {
            throw new TamperedTransactionException('Unsupported asset type for EVM verification.');
        }
    }

    /**
     * @param  list<mixed>  $params
     */
    private function rpc(string $url, string $method, array $params, bool $forBroadcast = false): mixed
    {
        $response = Http::timeout(25)->acceptJson()->post($url, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ]);
        if (! $response->successful()) {
            $this->throwRpc('RPC HTTP error: '.$response->status(), $forBroadcast);
        }
        $json = $response->json();
        if (isset($json['error'])) {
            $msg = is_array($json['error']) ? ($json['error']['message'] ?? json_encode($json['error'])) : (string) $json['error'];
            $this->throwRpc($msg, $forBroadcast);
        }

        return $json['result'] ?? null;
    }

    private function throwRpc(string $message, bool $forBroadcast): never
    {
        if ($forBroadcast) {
            throw new BroadcastRejectedException($message);
        }
        throw new ChainAdapterException($message);
    }

    private function hexWeiToDecimal(string $hex): string
    {
        $hex = preg_replace('/^0x/', '', $hex);
        if ($hex === '' || $hex === '0') {
            return '0';
        }
        if (function_exists('gmp_init')) {
            return gmp_strval(gmp_init($hex, 16), 10);
        }
        $dec = '0';
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $dec = bcmul($dec, '16', 0);
            $dec = bcadd($dec, (string) hexdec($hex[$i]), 0);
        }

        return $dec;
    }
}

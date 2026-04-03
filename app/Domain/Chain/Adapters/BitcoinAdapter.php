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

final class BitcoinAdapter implements ChainAdapterInterface
{
    public function supports(SupportedNetwork $network): bool
    {
        return $network->chain === 'BTC';
    }

    public function validateAddress(string $address, SupportedNetwork $network): bool
    {
        if (preg_match('/^(bc1|tb1)[a-z0-9]{20,80}$/i', $address)) {
            return true;
        }
        if (preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $address)) {
            return true;
        }

        return false;
    }

    public function normalizeAddress(string $address, SupportedNetwork $network): string
    {
        return $address;
    }

    public function buildConstructionPayload(TransactionIntent $intent, Asset $asset): array
    {
        $base = rtrim((string) config('artwallet_chains.bitcoin_esplora_url'), '/');

        return [
            'esplora_url' => $base,
            'feerate_sat_vb_hint' => $this->fetchFeerateSatPerVb($base),
        ];
    }

    public function estimateFees(SupportedNetwork $network, ?Asset $asset = null): FeeEstimateResult
    {
        $base = rtrim((string) config('artwallet_chains.bitcoin_esplora_url'), '/');
        $rate = $this->fetchFeerateSatPerVb($base);
        $ttl = (int) config('artwallet_chains.fee_estimate_ttl_seconds', 60);

        return new FeeEstimateResult(
            [
                'slow' => ['sat_per_vbyte' => max(1, (int) ($rate * 0.8))],
                'standard' => ['sat_per_vbyte' => max(1, $rate)],
                'fast' => ['sat_per_vbyte' => max(1, (int) ($rate * 1.2))],
            ],
            time() + $ttl,
        );
    }

    public function broadcastSignedTransaction(
        SupportedNetwork $network,
        string $rawSignedHex,
        TransactionIntent $intent,
    ): BroadcastResult {
        $base = rtrim((string) config('artwallet_chains.bitcoin_esplora_url'), '/');
        $hex = strtolower($rawSignedHex);
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }
        $binary = @hex2bin($hex);
        if ($binary === false) {
            throw new BroadcastRejectedException('Invalid BTC transaction hex.');
        }
        $response = Http::timeout(30)->withBody($binary, 'text/plain')->post($base.'/tx');
        if (! $response->successful()) {
            throw new BroadcastRejectedException('BTC broadcast failed: '.$response->body());
        }
        $txid = trim($response->body(), "\" \n\r\t");

        return new BroadcastResult($txid);
    }

    public function fetchTransactionStatus(SupportedNetwork $network, string $txid): array
    {
        $base = rtrim((string) config('artwallet_chains.bitcoin_esplora_url'), '/');
        $res = Http::timeout(20)->get($base.'/tx/'.$txid.'/status');
        if ($res->status() === 404) {
            return [
                'txid' => $txid,
                'confirmations' => 0,
                'status' => TransactionIntent::STATUS_PENDING,
                'block_height' => null,
                'raw' => [],
            ];
        }
        if (! $res->successful()) {
            throw new ChainAdapterException('BTC status lookup failed.');
        }
        $j = $res->json();
        $confirmed = (bool) ($j['confirmed'] ?? false);
        $height = $j['block_height'] ?? null;

        return [
            'txid' => $txid,
            'confirmations' => $confirmed ? 1 : 0,
            'status' => $confirmed ? TransactionIntent::STATUS_CONFIRMED : TransactionIntent::STATUS_PENDING,
            'block_height' => $height !== null ? (int) $height : null,
            'raw' => is_array($j) ? $j : [],
        ];
    }

    public function assertSignedTxMatchesIntent(
        TransactionIntent $intent,
        Asset $asset,
        string $rawSignedHex,
    ): void {
        if ($asset->asset_type !== 'native') {
            throw new TamperedTransactionException('BTC adapter only supports native BTC intents.');
        }
        $hex = strtolower($rawSignedHex);
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }
        $len = strlen($hex);
        if ($len < 20 || $len % 2 !== 0 || $len > 2_000_000) {
            throw new TamperedTransactionException('Invalid BTC signed transaction encoding.');
        }
    }

    private function fetchFeerateSatPerVb(string $base): int
    {
        try {
            $res = Http::timeout(15)->get($base.'/fee-estimates');
            if ($res->successful()) {
                $j = $res->json();
                if (is_array($j) && isset($j['6'])) {
                    return max(1, (int) ceil((float) $j['6']));
                }
            }
        } catch (\Throwable) {
        }

        return 10;
    }
}

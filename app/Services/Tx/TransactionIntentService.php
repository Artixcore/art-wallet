<?php

declare(strict_types=1);

namespace App\Services\Tx;

use App\Domain\Chain\ChainAdapterResolver;
use App\Domain\Chain\Exceptions\ChainAdapterException;
use App\Models\Asset;
use App\Models\SigningRequest;
use App\Models\SupportedNetwork;
use App\Models\TransactionIntent;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

final class TransactionIntentService
{
    public function __construct(
        private readonly IntentHashService $intentHash,
        private readonly ChainAdapterResolver $adapters,
    ) {}

    /**
     * @param  array{to_address: string, amount_atomic: string, memo?: ?string, asset_id: int, idempotency_client_key?: ?string}  $data
     * @return array{intent: TransactionIntent, signing_request: SigningRequest}
     */
    public function createOutgoingIntent(Wallet $wallet, array $data): array
    {
        $asset = Asset::query()->whereKey($data['asset_id'])->where('enabled', true)->firstOrFail();
        $network = SupportedNetwork::query()->whereKey($asset->supported_network_id)->where('enabled', true)->firstOrFail();

        $adapter = $this->adapters->forNetwork($network);
        $to = $data['to_address'];
        if (! $adapter->validateAddress($to, $network)) {
            throw new ChainAdapterException('Invalid recipient address for selected network.');
        }
        $toNormalized = $adapter->normalizeAddress($to, $network);

        $fromAddress = $this->resolveFromAddress($wallet, $network);
        if ($fromAddress === null) {
            throw new ChainAdapterException('No synced receive address for this network. Open Receive and sync addresses first.');
        }

        $memo = $data['memo'] ?? null;
        if ($memo !== null && $memo !== '' && ! $this->memoAllowedForChain($network->chain)) {
            throw new ChainAdapterException('Memo is not supported for this chain in the current build.');
        }

        $amount = $data['amount_atomic'];
        if (! preg_match('/^\d+$/', $amount) || bccomp($amount, '0', 0) <= 0) {
            throw new ChainAdapterException('amount_atomic must be a positive decimal integer string.');
        }

        $fromCanon = $network->chain === 'ETH' ? strtolower($fromAddress) : $fromAddress;
        $toCanon = $network->chain === 'ETH' ? strtolower($toNormalized) : $toNormalized;
        $canonicalFields = [
            'amount_atomic' => $amount,
            'asset_id' => $asset->id,
            'direction' => 'out',
            'from_address' => $fromCanon,
            'memo' => $memo,
            'schema_version' => IntentHashService::SCHEMA_VERSION,
            'supported_network_id' => $network->id,
            'to_address' => $toCanon,
            'wallet_public_id' => (string) $wallet->public_wallet_id,
        ];
        $canonical = $this->intentHash->canonicalJson($canonicalFields);
        $hash = $this->intentHash->hashCanonical($canonical);

        $ttlMin = (int) config('artwallet_chains.intent_ttl_minutes', 15);
        $expiresAt = now()->addMinutes($ttlMin);
        $signTtlMin = (int) config('artwallet_chains.signing_request_ttl_minutes', 10);

        return DB::transaction(function () use ($wallet, $asset, $network, $fromAddress, $toNormalized, $amount, $memo, $hash, $expiresAt, $signTtlMin, $data, $canonicalFields) {
            $intent = TransactionIntent::query()->create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'asset_id' => $asset->id,
                'supported_network_id' => $network->id,
                'direction' => 'out',
                'from_address' => $fromAddress,
                'to_address' => $toNormalized,
                'amount_atomic' => $amount,
                'memo' => $memo,
                'fee_quote_json' => null,
                'intent_hash' => $hash,
                'status' => TransactionIntent::STATUS_AWAITING_SIGNATURE,
                'expires_at' => $expiresAt,
                'idempotency_client_key' => $data['idempotency_client_key'] ?? null,
                'construction_payload_json' => null,
            ]);

            $intent->load(['asset', 'supportedNetwork']);
            $construction = $this->adapters->forNetwork($network)->buildConstructionPayload($intent, $asset);
            $intent->update(['construction_payload_json' => $construction]);

            $nonce = bin2hex(random_bytes(32));
            $signingRequest = SigningRequest::query()->create([
                'transaction_intent_id' => $intent->id,
                'server_nonce' => $nonce,
                'expires_at' => now()->addMinutes($signTtlMin),
                'consumed_at' => null,
            ]);

            return ['intent' => $intent->fresh(['asset', 'supportedNetwork']), 'signing_request' => $signingRequest];
        });
    }

    private function resolveFromAddress(Wallet $wallet, SupportedNetwork $network): ?string
    {
        $row = $wallet->walletAddresses()
            ->where(function ($q) use ($network) {
                $q->where('supported_network_id', $network->id)
                    ->orWhere('chain', $network->chain);
            })
            ->orderByDesc('supported_network_id')
            ->first();

        return $row?->address;
    }

    private function memoAllowedForChain(string $chain): bool
    {
        return in_array($chain, ['SOL', 'TRON'], true);
    }
}

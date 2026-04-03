<?php

declare(strict_types=1);

namespace App\Services\Tx;

use App\Domain\Chain\ChainAdapterResolver;
use App\Domain\Chain\Exceptions\BroadcastRejectedException;
use App\Models\BlockchainTransaction;
use App\Models\BroadcastAttempt;
use App\Models\SignedTransaction;
use App\Models\SigningRequest;
use App\Models\TransactionIntent;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BroadcastService
{
    public function __construct(
        private readonly ChainAdapterResolver $adapters,
        private readonly IntentVerificationService $verification,
    ) {}

    /**
     * @return array{txid: string, intent: TransactionIntent}
     */
    public function broadcast(User $user, TransactionIntent $intent, string $serverNonce, string $rawSignedHex, ?string $idempotencyKey = null): array
    {
        if ((int) $intent->user_id !== (int) $user->id) {
            abort(403);
        }
        if ($intent->isExpired()) {
            throw new BroadcastRejectedException('Transaction intent has expired. Create a new intent.');
        }
        if ($intent->status !== TransactionIntent::STATUS_AWAITING_SIGNATURE) {
            throw new BroadcastRejectedException('Intent is not awaiting signature.');
        }

        $signing = SigningRequest::query()
            ->where('transaction_intent_id', $intent->id)
            ->where('server_nonce', $serverNonce)
            ->whereNull('consumed_at')
            ->first();
        if ($signing === null || $signing->expires_at->isPast()) {
            throw new BroadcastRejectedException('Invalid or expired signing request.');
        }

        $idem = $idempotencyKey ?? Str::uuid()->toString();

        $prior = BroadcastAttempt::query()->where('idempotency_key', $idem)->first();
        if ($prior !== null) {
            if ((int) $prior->transaction_intent_id !== (int) $intent->id) {
                throw new BroadcastRejectedException('Idempotency key belongs to a different intent.');
            }
            $signed = SignedTransaction::query()->where('transaction_intent_id', $intent->id)->firstOrFail();

            return ['txid' => $signed->signed_tx_hash, 'intent' => $intent->fresh()];
        }

        return DB::transaction(function () use ($intent, $rawSignedHex, $signing, $idem) {
            $locked = TransactionIntent::query()->whereKey($intent->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== TransactionIntent::STATUS_AWAITING_SIGNATURE) {
                throw new BroadcastRejectedException('Intent already processed.');
            }

            $this->verification->assertSignedMatchesIntent($locked, $rawSignedHex);

            $adapter = $this->adapters->forNetwork($locked->supportedNetwork);
            $result = $adapter->broadcastSignedTransaction($locked->supportedNetwork, $rawSignedHex, $locked);

            BroadcastAttempt::query()->create([
                'transaction_intent_id' => $locked->id,
                'idempotency_key' => $idem,
                'rpc_label' => $locked->supportedNetwork->slug,
                'response_code' => 200,
                'error_class' => null,
                'attempted_at' => now(),
            ]);

            $signedHash = $result->txid;
            SignedTransaction::query()->create([
                'transaction_intent_id' => $locked->id,
                'signed_tx_hash' => $signedHash,
                'raw_signed_hex' => $rawSignedHex,
                'algorithm' => $locked->supportedNetwork->chain,
            ]);

            $signing->update(['consumed_at' => now()]);

            $locked->update([
                'status' => TransactionIntent::STATUS_PENDING,
            ]);

            $bt = BlockchainTransaction::query()->create([
                'txid' => $signedHash,
                'supported_network_id' => $locked->supported_network_id,
                'wallet_id' => $locked->wallet_id,
                'direction' => 'out',
                'counterparty_address' => $locked->to_address,
                'asset_id' => $locked->asset_id,
                'amount_atomic' => $locked->amount_atomic,
                'block_height' => null,
                'confirmations' => 0,
                'raw_metadata_json' => [],
                'transaction_intent_id' => $locked->id,
                'status' => BlockchainTransaction::STATUS_PENDING,
            ]);

            TransactionStatusHistory::query()->create([
                'blockchain_transaction_id' => $bt->id,
                'from_status' => null,
                'to_status' => BlockchainTransaction::STATUS_PENDING,
                'source' => 'broadcast',
                'observed_at' => now(),
            ]);

            return ['txid' => $signedHash, 'intent' => $locked->fresh()];
        });
    }
}

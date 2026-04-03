<?php

namespace App\Jobs;

use App\Domain\Chain\ChainAdapterResolver;
use App\Models\BlockchainTransaction;
use App\Models\TransactionStatusHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PollTransactionStatusJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $blockchainTransactionId,
    ) {}

    public function handle(ChainAdapterResolver $adapters): void
    {
        $tx = BlockchainTransaction::query()->find($this->blockchainTransactionId);
        if ($tx === null || $tx->status !== BlockchainTransaction::STATUS_PENDING) {
            return;
        }
        $tx->load('supportedNetwork');
        try {
            $adapter = $adapters->forNetwork($tx->supportedNetwork);
            $remote = $adapter->fetchTransactionStatus($tx->supportedNetwork, $tx->txid);
        } catch (\Throwable $e) {
            Log::warning('artwallet.poll_tx_failed', [
                'id' => $tx->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }
        $newStatus = $remote['status'];
        if ($newStatus === $tx->status && (int) $tx->confirmations === (int) $remote['confirmations']) {
            return;
        }
        $from = $tx->status;
        $tx->update([
            'status' => $newStatus,
            'confirmations' => (int) $remote['confirmations'],
            'block_height' => $remote['block_height'],
            'raw_metadata_json' => array_merge($tx->raw_metadata_json ?? [], ['last_poll' => $remote['raw']]),
        ]);
        TransactionStatusHistory::query()->create([
            'blockchain_transaction_id' => $tx->id,
            'from_status' => $from,
            'to_status' => $newStatus,
            'source' => 'poll',
            'observed_at' => now(),
        ]);
    }
}

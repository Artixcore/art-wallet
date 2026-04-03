<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlockchainTransaction extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DROPPED = 'dropped';

    protected $fillable = [
        'txid',
        'supported_network_id',
        'wallet_id',
        'direction',
        'counterparty_address',
        'asset_id',
        'amount_atomic',
        'block_height',
        'confirmations',
        'raw_metadata_json',
        'transaction_intent_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_metadata_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<SupportedNetwork, $this>
     */
    public function supportedNetwork(): BelongsTo
    {
        return $this->belongsTo(SupportedNetwork::class);
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<TransactionIntent, $this>
     */
    public function transactionIntent(): BelongsTo
    {
        return $this->belongsTo(TransactionIntent::class);
    }

    /**
     * @return HasMany<TransactionStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(TransactionStatusHistory::class);
    }
}

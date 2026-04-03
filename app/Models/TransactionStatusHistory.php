<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionStatusHistory extends Model
{
    protected $fillable = [
        'blockchain_transaction_id',
        'from_status',
        'to_status',
        'source',
        'observed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BlockchainTransaction, $this>
     */
    public function blockchainTransaction(): BelongsTo
    {
        return $this->belongsTo(BlockchainTransaction::class);
    }
}

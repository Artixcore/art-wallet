<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignedTransaction extends Model
{
    protected $fillable = [
        'transaction_intent_id',
        'signed_tx_hash',
        'raw_signed_hex',
        'algorithm',
    ];

    /**
     * @return BelongsTo<TransactionIntent, $this>
     */
    public function transactionIntent(): BelongsTo
    {
        return $this->belongsTo(TransactionIntent::class);
    }
}

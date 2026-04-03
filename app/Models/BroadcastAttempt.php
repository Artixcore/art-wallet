<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastAttempt extends Model
{
    protected $fillable = [
        'transaction_intent_id',
        'idempotency_key',
        'rpc_label',
        'response_code',
        'error_class',
        'attempted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TransactionIntent, $this>
     */
    public function transactionIntent(): BelongsTo
    {
        return $this->belongsTo(TransactionIntent::class);
    }
}

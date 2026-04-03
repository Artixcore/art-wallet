<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SigningRequest extends Model
{
    protected $fillable = [
        'transaction_intent_id',
        'server_nonce',
        'expires_at',
        'consumed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TransactionIntent, $this>
     */
    public function transactionIntent(): BelongsTo
    {
        return $this->belongsTo(TransactionIntent::class);
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }
}

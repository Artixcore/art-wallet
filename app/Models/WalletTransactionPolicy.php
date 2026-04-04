<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransactionPolicy extends Model
{
    protected $fillable = [
        'wallet_id',
        'confirm_above_amount',
        'fiat_currency',
        'require_second_approval',
        'settings_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirm_above_amount' => 'decimal:8',
            'require_second_approval' => 'boolean',
            'settings_version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}

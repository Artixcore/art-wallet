<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletSetting extends Model
{
    protected $fillable = [
        'wallet_id',
        'default_fee_tier',
        'show_testnet_assets',
        'settings_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_testnet_assets' => 'boolean',
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

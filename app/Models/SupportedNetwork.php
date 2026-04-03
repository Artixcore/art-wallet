<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportedNetwork extends Model
{
    protected $fillable = [
        'chain',
        'slug',
        'display_name',
        'chain_id',
        'hrp',
        'is_testnet',
        'explorer_tx_url_template',
        'enabled',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_testnet' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function explorerUrlForTxid(string $txid): ?string
    {
        $tpl = $this->explorer_tx_url_template;

        return $tpl ? str_replace('{txid}', $txid, $tpl) : null;
    }
}

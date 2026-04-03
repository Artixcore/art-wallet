<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    protected $fillable = [
        'code',
        'network',
        'supported_network_id',
        'asset_type',
        'decimals',
        'contract_address',
        'enabled',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SupportedNetwork, $this>
     */
    public function supportedNetwork(): BelongsTo
    {
        return $this->belongsTo(SupportedNetwork::class);
    }

    public function displayLabel(): string
    {
        if ($this->asset_type === 'native') {
            return $this->code;
        }

        return $this->code.' ('.strtoupper(str_replace('_', '-', $this->asset_type)).')';
    }
}

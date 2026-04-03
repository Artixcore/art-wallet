<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeEstimate extends Model
{
    protected $fillable = [
        'supported_network_id',
        'tier',
        'value_json',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_json' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SupportedNetwork, $this>
     */
    public function supportedNetwork(): BelongsTo
    {
        return $this->belongsTo(SupportedNetwork::class);
    }
}

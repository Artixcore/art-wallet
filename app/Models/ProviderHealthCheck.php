<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderHealthCheck extends Model
{
    protected $fillable = [
        'credential_id',
        'status',
        'latency_ms',
        'error_json',
        'checked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'error_json' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AgentApiCredential, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(AgentApiCredential::class, 'credential_id');
    }
}

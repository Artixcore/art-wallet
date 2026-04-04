<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RpcHealthCheck extends Model
{
    protected $fillable = [
        'chain',
        'provider',
        'status',
        'observed_at',
        'block_height',
        'latency_ms',
        'error_code',
        'detail_json',
        'probe_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
            'detail_json' => 'array',
            'block_height' => 'integer',
            'latency_ms' => 'integer',
        ];
    }
}

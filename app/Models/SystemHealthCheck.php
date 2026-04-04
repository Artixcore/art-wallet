<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemHealthCheck extends Model
{
    protected $fillable = [
        'subsystem',
        'check_key',
        'status',
        'observed_at',
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
            'latency_ms' => 'integer',
        ];
    }
}

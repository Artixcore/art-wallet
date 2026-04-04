<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityIncident extends Model
{
    protected $fillable = [
        'severity',
        'state',
        'summary',
        'correlation_keys',
        'opened_at',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correlation_keys' => 'array',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}

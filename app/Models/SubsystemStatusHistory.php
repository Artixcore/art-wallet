<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsystemStatusHistory extends Model
{
    protected $fillable = [
        'recorded_at',
        'subsystem',
        'snapshot_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'snapshot_json' => 'array',
        ];
    }
}

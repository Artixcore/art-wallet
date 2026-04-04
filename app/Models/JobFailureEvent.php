<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobFailureEvent extends Model
{
    protected $fillable = [
        'connection',
        'queue',
        'payload_class',
        'exception_class',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
        ];
    }
}

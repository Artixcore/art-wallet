<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDeliveryFailure extends Model
{
    protected $fillable = [
        'channel',
        'error_class',
        'count_in_window',
        'window_started_at',
        'metadata_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'window_started_at' => 'datetime',
            'metadata_json' => 'array',
            'count_in_window' => 'integer',
        ];
    }
}

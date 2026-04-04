<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderBenchmark extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'metrics_json',
        'sample_count',
        'window_start',
        'window_end',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metrics_json' => 'array',
            'window_start' => 'datetime',
            'window_end' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

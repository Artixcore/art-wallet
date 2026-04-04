<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagingSecurityEvent extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
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

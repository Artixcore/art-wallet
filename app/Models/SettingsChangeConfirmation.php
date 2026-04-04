<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingsChangeConfirmation extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'purpose',
        'payload_json',
        'expires_at',
        'consumed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
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

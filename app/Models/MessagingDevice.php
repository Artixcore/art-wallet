<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagingDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_ed25519_public_key_b64',
        'device_x25519_public_key_b64',
        'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}

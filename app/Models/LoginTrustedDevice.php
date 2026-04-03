<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoginTrustedDevice extends Model
{
    protected $fillable = [
        'user_id',
        'public_key',
        'key_alg',
        'trust_version',
        'device_label_ciphertext',
        'fingerprint_signals_json',
        'trusted_at',
        'last_used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'fingerprint_signals_json' => 'array',
            'trusted_at' => 'datetime',
            'last_used_at' => 'datetime',
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

    /**
     * @return HasMany<DeviceChallenge, $this>
     */
    public function challenges(): HasMany
    {
        return $this->hasMany(DeviceChallenge::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}

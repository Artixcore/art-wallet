<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceChallenge extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'public_uuid',
        'user_id',
        'login_trusted_device_id',
        'challenge_nonce',
        'client_code',
        'session_binding_hash',
        'purpose',
        'expires_at',
        'consumed_at',
        'signature',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'created_at' => 'datetime',
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
     * @return BelongsTo<LoginTrustedDevice, $this>
     */
    public function loginTrustedDevice(): BelongsTo
    {
        return $this->belongsTo(LoginTrustedDevice::class);
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}

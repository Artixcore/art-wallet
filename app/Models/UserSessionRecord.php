<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSessionRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'session_id_hash',
        'login_trusted_device_id',
        'ip_hash',
        'user_agent_hash',
        'created_at',
        'last_seen_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'last_seen_at' => 'datetime',
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
     * @return BelongsTo<LoginTrustedDevice, $this>
     */
    public function loginTrustedDevice(): BelongsTo
    {
        return $this->belongsTo(LoginTrustedDevice::class);
    }
}

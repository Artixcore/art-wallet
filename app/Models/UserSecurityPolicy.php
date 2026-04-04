<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSecurityPolicy extends Model
{
    protected $fillable = [
        'user_id',
        'idle_timeout_minutes',
        'max_session_duration_minutes',
        'notify_new_device_login',
        'settings_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'idle_timeout_minutes' => 'integer',
            'max_session_duration_minutes' => 'integer',
            'notify_new_device_login' => 'boolean',
            'settings_version' => 'integer',
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

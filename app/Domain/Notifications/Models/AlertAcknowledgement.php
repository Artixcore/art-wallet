<?php

namespace App\Domain\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'in_app_notification_id',
    'acknowledged_at',
    'ip_hash',
    'user_agent_hash',
])]
class AlertAcknowledgement extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<InAppNotification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(InAppNotification::class, 'in_app_notification_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }
}

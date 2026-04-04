<?php

namespace App\Domain\Notifications\Models;

use App\Domain\Notifications\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'category',
    'toast_enabled',
    'persist_enabled',
    'email_enabled',
])]
class UserNotificationPreference extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'toast_enabled' => 'boolean',
            'persist_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'category' => NotificationCategory::class,
        ];
    }
}

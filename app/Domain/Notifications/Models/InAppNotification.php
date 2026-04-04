<?php

namespace App\Domain\Notifications\Models;

use App\Domain\Notifications\Enums\NotificationCategory;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id',
    'category',
    'severity',
    'title_key',
    'body_params',
    'action_url',
    'dedupe_key',
    'subject_type',
    'subject_id',
    'requires_ack',
    'blocking',
    'read_at',
    'acknowledged_at',
    'expires_at',
])]
class InAppNotification extends Model
{
    protected $table = 'in_app_notifications';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<InAppNotification>  $query
     * @return Builder<InAppNotification>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * @param  Builder<InAppNotification>  $query
     * @return Builder<InAppNotification>
     */
    public function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->where('requires_ack', true)->whereNull('acknowledged_at');
    }

    /**
     * @param  Builder<InAppNotification>  $query
     * @return Builder<InAppNotification>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function needsAcknowledgment(): bool
    {
        return $this->requires_ack && $this->acknowledged_at === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body_params' => 'array',
            'requires_ack' => 'boolean',
            'blocking' => 'boolean',
            'read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'expires_at' => 'datetime',
            'category' => NotificationCategory::class,
            'severity' => NotificationSeverity::class,
        ];
    }
}

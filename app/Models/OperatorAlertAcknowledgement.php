<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorAlertAcknowledgement extends Model
{
    protected $fillable = [
        'system_alert_id',
        'operator_user_id',
        'ack_at',
        'comment',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ack_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SystemAlert, $this>
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(SystemAlert::class, 'system_alert_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }
}

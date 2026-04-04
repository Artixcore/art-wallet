<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskThresholdSetting extends Model
{
    protected $fillable = [
        'user_id',
        'large_tx_alert_fiat',
        'large_tx_alert_currency',
        'settings_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'large_tx_alert_fiat' => 'decimal:2',
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

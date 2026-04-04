<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagingPrivacySetting extends Model
{
    protected $fillable = [
        'user_id',
        'read_receipts_enabled',
        'typing_indicators_enabled',
        'max_attachment_mb',
        'safety_warnings_enabled',
        'settings_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_receipts_enabled' => 'boolean',
            'typing_indicators_enabled' => 'boolean',
            'max_attachment_mb' => 'integer',
            'safety_warnings_enabled' => 'boolean',
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

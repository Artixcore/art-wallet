<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupState extends Model
{
    protected $fillable = [
        'user_id',
        'mnemonic_verified_at',
        'recovery_kit_created_at',
        'server_backup_uploaded_at',
        'hint_public',
        'strict_security_mode',
    ];

    protected function casts(): array
    {
        return [
            'mnemonic_verified_at' => 'datetime',
            'recovery_kit_created_at' => 'datetime',
            'server_backup_uploaded_at' => 'datetime',
            'strict_security_mode' => 'boolean',
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

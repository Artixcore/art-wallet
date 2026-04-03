<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'audit_logs';
    }

    public $timestamps = false;

    protected $fillable = [
        'occurred_at',
        'tenant_id',
        'actor_type',
        'actor_id',
        'subject_type',
        'subject_id',
        'action',
        'target_type',
        'target_id',
        'diff',
        'diff_json_id',
        'ip_address',
        'user_agent',
        'session_id',
        'device_id',
        'request_id',
        'prev_hash',
        'row_hash',
        'signature',
        'immutable',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'diff' => 'array',
            'immutable' => 'boolean',
        ];
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function diffEncryptedMetadata(): BelongsTo
    {
        return $this->belongsTo(EncryptedMetadata::class, 'diff_json_id');
    }
}

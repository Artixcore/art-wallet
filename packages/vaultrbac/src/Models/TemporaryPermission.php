<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TemporaryPermission extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'temporary_permissions';
    }

    protected $fillable = [
        'tenant_id',
        'model_type',
        'model_id',
        'permission_id',
        'granted_by',
        'valid_from',
        'valid_until',
        'reason',
        'approval_request_id',
        'metadata_json_id',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, string|int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeValidAt(Builder $query, \DateTimeInterface $at): Builder
    {
        return $query
            ->where('valid_from', '<=', $at)
            ->where('valid_until', '>=', $at);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function metadataEncrypted(): BelongsTo
    {
        return $this->belongsTo(EncryptedMetadata::class, 'metadata_json_id');
    }

    /**
     * Subject receiving the temporary permission (morph keys: model_type, model_id).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }
}

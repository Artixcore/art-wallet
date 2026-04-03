<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Casts\MaybeEncryptedApprovalPayload;
use Artwallet\VaultRbac\Enums\ApprovalStatus;
use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'approval_requests';
    }

    protected $fillable = [
        'tenant_id',
        'requester_id',
        'subject_type',
        'subject_id',
        'payload',
        'status',
        'required_approvers',
        'decided_by',
        'decided_at',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'payload' => MaybeEncryptedApprovalPayload::class,
            'required_approvers' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ApprovalStatus::Pending);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, string|int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
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
            'required_approvers' => 'array',
            'decided_at' => 'datetime',
        ];
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

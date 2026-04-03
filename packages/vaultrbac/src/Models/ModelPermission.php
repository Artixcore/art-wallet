<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Casts\MaybeEncryptedJson;
use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelPermission extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'model_permissions';
    }

    protected $fillable = [
        'tenant_id',
        'team_id',
        'team_key',
        'permission_id',
        'model_type',
        'model_id',
        'effect',
        'assigned_by',
        'assigned_at',
        'expires_at',
        'suspended_at',
        'approval_request_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'expires_at' => 'datetime',
            'suspended_at' => 'datetime',
            'metadata' => MaybeEncryptedJson::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->team_key = $model->team_id !== null ? (int) $model->team_id : 0;
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}

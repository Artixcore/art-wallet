<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PermissionScope extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'permission_scopes';
    }

    protected $fillable = [
        'tenant_id',
        'scope_type',
        'scope_model_type',
        'scope_model_id',
        'permission_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    public function scopeModel(): MorphTo
    {
        return $this->morphTo();
    }
}

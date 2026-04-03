<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionCacheVersion extends Model
{
    use MapsVaultRbacTable;

    public const UPDATED_AT = 'updated_at';

    public const CREATED_AT = null;

    protected static function vaultTableKey(): string
    {
        return 'permission_cache_versions';
    }

    protected $fillable = [
        'tenant_id',
        'scope',
        'subject_type',
        'subject_id',
        'version',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'subject_id' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionInheritance extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'permission_inheritance';
    }

    protected $fillable = [
        'tenant_id',
        'ancestor_permission_id',
        'descendant_permission_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function ancestorPermission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'ancestor_permission_id');
    }

    public function descendantPermission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'descendant_permission_id');
    }
}

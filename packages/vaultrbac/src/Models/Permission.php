<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Casts\MaybeEncryptedJson;
use Artwallet\VaultRbac\Database\VaultrbacTables;
use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'permissions';
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'permission_group',
        'description',
        'is_wildcard_parent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_wildcard_parent' => 'boolean',
            'metadata' => MaybeEncryptedJson::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            VaultrbacTables::name('role_permission'),
            'permission_id',
            'role_id',
        )->using(RolePermission::class)
            ->withTimestamps()
            ->withPivot([
                'tenant_id',
                'granted_at',
                'expires_at',
                'source',
                'condition_id',
            ]);
    }
}

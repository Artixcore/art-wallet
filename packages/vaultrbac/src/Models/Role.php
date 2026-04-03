<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Casts\MaybeEncryptedJson;
use Artwallet\VaultRbac\Database\VaultrbacTables;
use Artwallet\VaultRbac\Enums\RoleStatus;
use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'roles';
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'display_name',
        'description',
        'parent_role_id',
        'is_system',
        'activation_state',
        'metadata',
        'integrity_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'activation_state' => RoleStatus::class,
            'metadata' => MaybeEncryptedJson::class,
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTenantOrGlobal(Builder $query, string|int $tenantId): Builder
    {
        return $query->where(function (Builder $q) use ($tenantId): void {
            $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activation_state', RoleStatus::Active);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function parentRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_role_id');
    }

    public function childRoles(): HasMany
    {
        return $this->hasMany(Role::class, 'parent_role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            VaultrbacTables::name('role_permission'),
            'role_id',
            'permission_id',
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

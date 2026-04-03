<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Database\Factories\TenantFactory;
use Artwallet\VaultRbac\Enums\TenantStatus;
use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;
    use MapsVaultRbacTable;

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    protected static function vaultTableKey(): string
    {
        return 'tenants';
    }

    protected $fillable = [
        'slug',
        'name',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'settings' => 'array',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TenantStatus::Active);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'tenant_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'tenant_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'tenant_id');
    }
}

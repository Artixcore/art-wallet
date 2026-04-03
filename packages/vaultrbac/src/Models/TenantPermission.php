<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Casts\ValidatedJsonArray;
use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPermission extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'tenant_permissions';
    }

    protected $fillable = [
        'tenant_id',
        'permission_id',
        'is_enabled',
        'effective_from',
        'effective_until',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'metadata' => ValidatedJsonArray::class,
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
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeEffectiveNow(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $now);
            })
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('effective_until')->orWhere('effective_until', '>=', $now);
            });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}

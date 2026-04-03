<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Casts\ValidatedJsonArray;
use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRole extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'tenant_roles';
    }

    protected $fillable = [
        'tenant_id',
        'role_id',
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
     * Rows whose effective window includes "now" (null bounds mean open-ended).
     *
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

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}

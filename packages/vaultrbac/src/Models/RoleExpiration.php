<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Enums\RoleExpirationTarget;
use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Schedules expiry for an assignment or catalog row.
 *
 * {@see RoleExpirationTarget} defines {@code target} discriminator values (e.g. pivot table name).
 * {@code target_id} is the primary key of that row — resolve via repository, not a generic morph.
 */
class RoleExpiration extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'role_expirations';
    }

    protected $fillable = [
        'tenant_id',
        'target',
        'target_id',
        'expires_at',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'target' => RoleExpirationTarget::class,
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
    public function scopeDueAfter(Builder $query, \DateTimeInterface $from): Builder
    {
        return $query->where('expires_at', '>=', $from);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

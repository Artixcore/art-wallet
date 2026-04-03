<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Database\Factories\TeamFactory;
use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Team extends Model
{
    use HasFactory;
    use MapsVaultRbacTable;

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }

    protected static function vaultTableKey(): string
    {
        return 'teams';
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionCondition extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'permission_conditions';
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'expression',
        'evaluator_key',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'expression' => 'array',
            'version' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

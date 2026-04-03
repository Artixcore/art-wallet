<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;

class CacheVersion extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'cache_versions';
    }

    protected $primaryKey = 'cache_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'cache_key',
        'version',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'updated_at' => 'datetime',
        ];
    }
}

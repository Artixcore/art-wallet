<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;

class SuperUserAction extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'super_user_actions';
    }

    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'action',
        'justification',
        'payload',
        'occurred_at',
        'ip_address',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models\Concerns;

use Artwallet\VaultRbac\Database\VaultrbacTables;

trait MapsVaultRbacTable
{
    abstract protected static function vaultTableKey(): string;

    public function getTable(): string
    {
        return VaultrbacTables::name(static::vaultTableKey());
    }
}

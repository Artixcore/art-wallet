<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Database;

use Artwallet\VaultRbac\Exceptions\ConfigurationException;

/**
 * Resolves configured table names for migrations and models.
 */
final class VaultrbacTables
{
    public static function name(string $key): string
    {
        $tables = config('vaultrbac.tables', []);

        if (! isset($tables[$key]) || $tables[$key] === '') {
            throw new ConfigurationException("Missing vaultrbac.tables.{$key} configuration.");
        }

        return (string) $tables[$key];
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;

/**
 * Registers {@see Blueprint} macros for optional UUID primary keys in published / stub migrations.
 */
final class BlueprintMacros
{
    public static function register(): void
    {
        Blueprint::macro('rbacId', function (?string $column = null): Blueprint {
            /** @var Blueprint $this */
            $col = $column ?? 'id';

            if (Config::get('vaultrbac.ids.type', 'bigint') === 'uuid') {
                $this->uuid($col)->primary();

                return $this;
            }

            if ($col === 'id') {
                $this->id();
            } else {
                $this->unsignedBigInteger($col, true);
                $this->primary($col);
            }

            return $this;
        });
    }
}

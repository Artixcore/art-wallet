<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Database\VaultrbacTables;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class DoctorCommand extends Command
{
    protected $signature = 'vaultrbac:doctor';

    protected $description = 'Validate VaultRBAC configuration and required database tables';

    public function handle(ConfigRepository $config): int
    {
        $this->info('VaultRBAC doctor');

        $ok = true;

        try {
            VaultrbacTables::name('tenants');
        } catch (Throwable $e) {
            $this->error('Invalid table map: '.$e->getMessage());
            $ok = false;
        }

        $tables = (array) $config->get('vaultrbac.tables', []);
        foreach ($tables as $key => $table) {
            if (! is_string($table) || $table === '') {
                $this->warn("Table key [{$key}] is empty.");
                $ok = false;

                continue;
            }

            if (! Schema::hasTable($table)) {
                $this->error("Missing table [{$table}] (config key vaultrbac.tables.{$key}).");
                $ok = false;
            } else {
                $this->line("OK  {$table}");
            }
        }

        $appKey = $config->get('app.key');
        if (! is_string($appKey) || $appKey === '') {
            $this->error('APP_KEY is not set.');
            $ok = false;
        }

        if ($config->get('vaultrbac.audit.enabled') && ! is_string($config->get('vaultrbac.audit.secret')) && (! is_string($appKey) || $appKey === '')) {
            $this->warn('Audit is enabled but vaultrbac.audit.secret is empty; ensure APP_KEY is set for DatabaseAuditSink.');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}

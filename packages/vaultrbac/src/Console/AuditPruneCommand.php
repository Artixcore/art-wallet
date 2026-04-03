<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Database\VaultrbacTables;
use Artwallet\VaultRbac\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class AuditPruneCommand extends Command
{
    protected $signature = 'vaultrbac:audit:prune
                            {--older-than= : ISO date or days integer (default 365 days)}
                            {--dry-run : Count only}
                            {--force : Confirm destructive delete}';

    protected $description = 'Delete old audit log rows (breaks hash chain tail — use with care)';

    public function handle(): int
    {
        $table = VaultrbacTables::name('audit_logs');
        if (! Schema::hasTable($table)) {
            $this->error('Audit table missing.');

            return self::FAILURE;
        }

        $raw = $this->option('older-than');
        $cutoff = $this->resolveCutoff($raw);
        $query = AuditLog::query()->where('occurred_at', '<', $cutoff);
        $count = $query->count();

        if ($count === 0) {
            $this->info('No rows to prune.');

            return self::SUCCESS;
        }

        $this->warn("Matched {$count} row(s) before {$cutoff->toDateTimeString()}.");

        if ((bool) $this->option('dry-run')) {
            return self::SUCCESS;
        }

        if (! (bool) $this->option('force') && ! $this->confirm('Permanently delete these audit rows?')) {
            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} row(s).");

        return self::SUCCESS;
    }

    private function resolveCutoff(mixed $raw): Carbon
    {
        if ($raw === null || $raw === '') {
            return Carbon::now()->subDays(365);
        }

        if (is_numeric($raw)) {
            return Carbon::now()->subDays((int) $raw);
        }

        return Carbon::parse((string) $raw);
    }
}

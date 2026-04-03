<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Illuminate\Console\Command;

/**
 * Backwards-compatible entry point; delegates to {@see DiagnoseCommand}.
 */
final class DoctorCommand extends Command
{
    protected $signature = 'vaultrbac:doctor {--tenant=} {--json}';

    protected $description = 'Alias for vaultrbac:diagnose';

    public function handle(): int
    {
        $options = [];
        if ($this->option('tenant') !== null) {
            $options['--tenant'] = $this->option('tenant');
        }
        if ($this->option('json')) {
            $options['--json'] = true;
        }

        return $this->call('vaultrbac:diagnose', $options);
    }
}

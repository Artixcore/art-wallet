<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Production and CI guardrails: fail closed on dangerous configuration and repo hygiene mistakes.
 */
final class OpsValidateProductionCommand extends Command
{
    protected $signature = 'ops:validate
                            {--ci : Static checks for CI (tracked .env, no real APP_KEY in .env.example)}
                            {--permissions : Warn if storage/bootstrap/cache are not writable (production host)}
                            {--backup-script= : Optional path to backup script; must exist if set}';

    protected $description = 'Validate production configuration and repository guardrails';

    public function handle(): int
    {
        if ($this->option('ci')) {
            return $this->runCiChecks();
        }

        $code = $this->runProductionChecks();
        if ($code !== 0) {
            return $code;
        }

        if ($this->option('permissions')) {
            $this->checkWritableDirectories();
        }

        $backupScript = $this->option('backup-script');
        if (is_string($backupScript) && $backupScript !== '') {
            if (! is_file($backupScript) || ! is_readable($backupScript)) {
                $this->error("Backup script not found or not readable: {$backupScript}");

                return 1;
            }
            if (! is_executable($backupScript)) {
                $this->warn("Backup script is not executable: {$backupScript}");
            }
        }

        return 0;
    }

    private function runCiChecks(): int
    {
        $failed = false;

        if ($this->envFileIsGitTracked()) {
            $this->error('The .env file must not be committed to git.');
            $failed = true;
        } else {
            $this->info('OK: .env is not tracked by git.');
        }

        $examplePath = base_path('.env.example');
        if (is_readable($examplePath) && $this->envExampleContainsRealisticAppKey($examplePath)) {
            $this->error('.env.example appears to contain a non-placeholder APP_KEY; use APP_KEY= only.');
            $failed = true;
        } else {
            $this->info('OK: .env.example APP_KEY is not a realistic secret.');
        }

        if ($failed) {
            return 1;
        }

        $this->info('CI guardrails passed.');

        return 0;
    }

    private function envFileIsGitTracked(): bool
    {
        if (! is_dir(base_path('.git'))) {
            return false;
        }

        $process = new Process(['git', 'ls-files', '--cached', '--', '.env'], base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            return false;
        }

        return trim($process->getOutput()) !== '';
    }

    private function envExampleContainsRealisticAppKey(string $path): bool
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            return false;
        }

        if (! preg_match('/^APP_KEY=(.+)$/m', $content, $m)) {
            return false;
        }

        $val = trim($m[1], " \t\"'");
        if ($val === '' || str_contains($val, '${')) {
            return false;
        }

        // Laravel base64 keys are typically 44 chars; treat long base64-like values as suspicious in .env.example
        if (preg_match('/^[A-Za-z0-9+\/]+=*$/', $val) && strlen($val) >= 40) {
            return true;
        }

        return false;
    }

    private function runProductionChecks(): int
    {
        if (config('app.env') !== 'production') {
            $this->warn('APP_ENV is not production; skipping strict production checks (use in production deploy).');

            return 0;
        }

        if (config('app.debug')) {
            $this->error('APP_DEBUG must be false in production.');

            return 1;
        }

        $key = (string) config('app.key');
        if ($key === '') {
            $this->error('APP_KEY must be set in production.');

            return 1;
        }

        $this->info('OK: production APP_DEBUG and APP_KEY look sane.');

        return 0;
    }

    private function checkWritableDirectories(): void
    {
        $paths = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            bootstrap_path('cache'),
        ];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                $this->warn("Missing directory: {$path}");

                continue;
            }
            if (! is_writable($path)) {
                $this->warn("Not writable (fix for production): {$path}");
            }
        }
    }
}

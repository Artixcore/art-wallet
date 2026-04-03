<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Api\Dto\CacheWarmTarget;
use Artwallet\VaultRbac\Contracts\PermissionCacheAdminInterface;
use Illuminate\Console\Command;

final class CacheFlushCommand extends Command
{
    protected $signature = 'vaultrbac:cache:flush
                            {--tenant= : Tenant id}
                            {--user= : Subject model id}
                            {--all-tenants : All tenants (confirmation required)}
                            {--no-bump : Do not bump permission version rows}
                            {--force : Required for --all-tenants}';

    protected $description = 'Flush VaultRBAC warm snapshots and optionally bump permission versions';

    public function handle(PermissionCacheAdminInterface $admin): int
    {
        $all = (bool) $this->option('all-tenants');
        if ($all && ! (bool) $this->option('force')) {
            $this->error('Use --force with --all-tenants.');

            return self::FAILURE;
        }
        if ($all && ! $this->confirm('Flush cache for ALL tenants?')) {
            return self::SUCCESS;
        }

        $target = new CacheWarmTarget(
            tenantId: $this->option('tenant'),
            userId: $this->option('user'),
            allTenants: $all,
        );

        $bump = ! (bool) $this->option('no-bump');
        $result = $admin->flush($target, $bump);
        if (! $result->success) {
            $this->error($result->message ?? 'Flush failed.');

            return self::FAILURE;
        }

        $this->info("Processed {$result->keysRemovedOrBumped} flush operation(s).");

        return self::SUCCESS;
    }
}

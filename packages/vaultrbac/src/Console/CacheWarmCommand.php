<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Api\Dto\CacheWarmTarget;
use Artwallet\VaultRbac\Contracts\PermissionCacheAdminInterface;
use Illuminate\Console\Command;

final class CacheWarmCommand extends Command
{
    protected $signature = 'vaultrbac:cache:warm
                            {--tenant= : Tenant id}
                            {--user= : Subject model id (numeric)}
                            {--all-tenants : Warm every tenant (confirmation required)}';

    protected $description = 'Snapshot permission cache versions into the application cache';

    public function handle(PermissionCacheAdminInterface $admin): int
    {
        $all = (bool) $this->option('all-tenants');
        if ($all && ! $this->confirm('Warm cache for ALL tenants? This may be expensive.')) {
            return self::SUCCESS;
        }

        $target = new CacheWarmTarget(
            tenantId: $this->option('tenant'),
            userId: $this->option('user'),
            allTenants: $all,
        );

        $result = $admin->warm($target);
        if (! $result->success) {
            $this->error($result->message ?? 'Warm failed.');

            return self::FAILURE;
        }

        $this->info("Warmed {$result->entriesWarmed} cache entr(y/ies).");

        return self::SUCCESS;
    }
}

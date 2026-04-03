<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Models\Permission;
use Illuminate\Console\Command;

final class PermissionCreateCommand extends Command
{
    protected $signature = 'vaultrbac:permission:create
                            {name : Permission machine name}
                            {--tenant= : Tenant id (omit for global permission)}
                            {--group= : permission_group}
                            {--wildcard : Mark as wildcard parent}';

    protected $description = 'Create a permission definition row';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if ($name === '') {
            $this->error('Name required.');

            return self::FAILURE;
        }

        $tenant = $this->option('tenant');
        $perm = Permission::query()->create([
            'tenant_id' => $tenant !== null ? $tenant : null,
            'name' => $name,
            'permission_group' => $this->option('group'),
            'is_wildcard_parent' => (bool) $this->option('wildcard'),
        ]);

        $this->info("Permission created id={$perm->getKey()}.");

        return self::SUCCESS;
    }
}

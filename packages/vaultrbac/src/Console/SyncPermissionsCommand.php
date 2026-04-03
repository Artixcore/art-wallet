<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'vaultrbac:sync-permissions
                            {--tenant= : Tenant id for tenant-scoped permissions}
                            {--global : Create global permissions (tenant_id null)}';

    protected $description = 'Upsert permission rows from config vaultrbac.sync.permissions';

    public function handle(ConfigRepository $config): int
    {
        $permissions = (array) $config->get('vaultrbac.sync.permissions', []);

        if ($permissions === []) {
            $this->warn('No permissions configured (vaultrbac.sync.permissions).');

            return self::SUCCESS;
        }

        $global = (bool) $this->option('global');
        $tenant = $this->option('tenant');

        if ($global && $tenant !== null) {
            $this->error('Use either --global or --tenant=, not both.');

            return self::FAILURE;
        }

        if (! $global && $tenant === null) {
            $this->error('Provide --tenant=id or --global for permission scope.');

            return self::FAILURE;
        }

        $tenantId = $global ? null : $tenant;

        $created = 0;
        foreach ($permissions as $item) {
            if (is_string($item)) {
                $name = $item;
                $attributes = [];
            } elseif (is_array($item) && isset($item['name']) && is_string($item['name'])) {
                $name = $item['name'];
                $attributes = $item;
                unset($attributes['name'], $attributes['tenant_id']);
            } else {
                $this->warn('Skipping invalid permission entry.');

                continue;
            }

            $row = Permission::query()->firstOrCreate(
                [
                    'name' => $name,
                    'tenant_id' => $tenantId,
                ],
                array_merge([
                    'is_wildcard_parent' => false,
                ], $attributes),
            );

            if ($row->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->info(sprintf('Synced %d permission definition(s); %d newly created.', count($permissions), $created));

        return self::SUCCESS;
    }
}

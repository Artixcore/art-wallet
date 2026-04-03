<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\Enums\RoleStatus;
use Artwallet\VaultRbac\Models\Role;
use Illuminate\Console\Command;

final class RoleCreateCommand extends Command
{
    protected $signature = 'vaultrbac:role:create
                            {name : Role machine name}
                            {--tenant= : Tenant id (omit for global role)}
                            {--display= : Display name}';

    protected $description = 'Create a role row';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if ($name === '') {
            $this->error('Name required.');

            return self::FAILURE;
        }

        $tenant = $this->option('tenant');
        $role = Role::query()->create([
            'tenant_id' => $tenant !== null ? $tenant : null,
            'name' => $name,
            'display_name' => $this->option('display') ?: $name,
            'activation_state' => RoleStatus::Active,
        ]);

        $this->info("Role created id={$role->getKey()}.");

        return self::SUCCESS;
    }
}

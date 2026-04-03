<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\VaultRbac;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

final class RoleAssignCommand extends Command
{
    protected $signature = 'vaultrbac:role:assign
                            {user : User model id}
                            {role : Role name or id}
                            {tenant : Tenant id}
                            {--team= : Optional team id}
                            {--by= : assigned_by user id}';

    protected $description = 'Assign a role to a user model via VaultRbac';

    public function handle(VaultRbac $rbac): int
    {
        $userModel = $this->resolveUserModel((string) $this->argument('user'));
        if (! $userModel instanceof Model) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        try {
            $rbac->assignRole(
                $userModel,
                $this->argument('role'),
                $this->argument('tenant'),
                $this->option('team'),
                $this->option('by'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Role assigned.');

        return self::SUCCESS;
    }

    private function resolveUserModel(string $id): ?Model
    {
        $class = config('auth.providers.users.model');
        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        /** @var class-string<Model> $class */
        return $class::query()->find($id);
    }
}

<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Console;

use Artwallet\VaultRbac\VaultRbac;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

final class PermissionAssignCommand extends Command
{
    protected $signature = 'vaultrbac:permission:assign
                            {user : User model id}
                            {permission : Permission name or id}
                            {tenant : Tenant id}
                            {--team= : Optional team id}
                            {--effect=allow : allow or deny}
                            {--by= : assigned_by user id}';

    protected $description = 'Grant or deny a direct model permission via VaultRbac';

    public function handle(VaultRbac $rbac): int
    {
        $userModel = $this->resolveUserModel((string) $this->argument('user'));
        if (! $userModel instanceof Model) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $effect = (string) $this->option('effect');
        if (! in_array($effect, ['allow', 'deny'], true)) {
            $this->error('effect must be allow or deny.');

            return self::FAILURE;
        }

        try {
            $rbac->givePermission(
                $userModel,
                $this->argument('permission'),
                $this->argument('tenant'),
                $this->option('team'),
                $effect,
                $this->option('by'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Permission assigned.');

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

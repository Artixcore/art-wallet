<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Diagnostics;

use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

/**
 * @group diagnostics
 */
final class CacheFlushCommandTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_flush_does_not_delete_roles(): void
    {
        $tenant = $this->createTenant();
        $role = $this->createRoleForTenant($tenant, 'keep-me');

        $exit = Artisan::call('vaultrbac:cache:flush', [
            '--tenant' => (string) $tenant->getKey(),
        ]);

        self::assertSame(0, $exit);
        self::assertNotNull(Role::query()->find($role->getKey()));
    }
}

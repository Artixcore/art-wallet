<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Feature;

use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

final class BladeVaultCanFeatureTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_vaultcan_renders_branch_when_allowed(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'b');
        $perm = $this->createPermissionForTenant($tenant, 'blade.show');
        $this->attachPermissionToRole($role, $perm, $tenant);
        $this->vault()->assignRole($user, $role, $tenant->getKey());

        $this->actingAs($user);

        $html = Blade::render(<<<'BLADE'
@vaultcan('blade.show')
YES
@endvaultcan
BLADE
        );

        self::assertStringContainsString('YES', $html);
    }

    public function test_vaultcan_hides_when_denied(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $this->actingAs($user);

        $html = Blade::render(<<<'BLADE'
@vaultcan('blade.hidden')
NO
@endvaultcan
BLADE
        );

        self::assertStringNotContainsString('NO', $html);
    }
}

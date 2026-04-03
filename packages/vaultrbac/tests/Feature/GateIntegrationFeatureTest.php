<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Feature;

use Artwallet\VaultRbac\Tests\Concerns\InteractsWithVaultRbac;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

final class GateIntegrationFeatureTest extends TestCase
{
    use InteractsWithVaultRbac;

    public function test_vault_gate_ability_delegates_to_resolver(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $role = $this->createRoleForTenant($tenant, 'g');
        $perm = $this->createPermissionForTenant($tenant, 'gate.target');
        $this->attachPermissionToRole($role, $perm, $tenant);
        $this->vault()->assignRole($user, $role, $tenant->getKey());

        $ability = (string) config('vaultrbac.gate.ability');

        self::assertTrue(Gate::forUser($user)->allows($ability, 'gate.target'));
    }

    public function test_malformed_gate_permission_argument_denies(): void
    {
        $tenant = $this->createTenant();
        $this->withDefaultTenant($tenant);

        $user = $this->createUser();
        $ability = (string) config('vaultrbac.gate.ability');

        self::assertFalse(Gate::forUser($user)->allows($ability, ''));
        self::assertFalse(Gate::forUser($user)->allows($ability, 123));
    }
}

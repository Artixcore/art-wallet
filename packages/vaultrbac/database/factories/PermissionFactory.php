<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Database\Factories;

use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
final class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'permission.'.fake()->unique()->numerify('########'),
            'permission_group' => null,
            'description' => null,
            'is_wildcard_parent' => false,
            'metadata' => null,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => ['tenant_id' => $tenant->getKey()]);
    }

    public function global(): static
    {
        return $this->state(fn (): array => ['tenant_id' => null]);
    }
}

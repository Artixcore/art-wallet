<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Database\Factories;

use Artwallet\VaultRbac\Enums\RoleStatus;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'role_'.fake()->unique()->numberBetween(1, 1_000_000),
            'display_name' => fake()->jobTitle(),
            'description' => null,
            'parent_role_id' => null,
            'is_system' => false,
            'activation_state' => RoleStatus::Active,
            'metadata' => null,
            'integrity_hash' => null,
        ];
    }

    /**
     * Tenant-scoped role (explicit tenant).
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => ['tenant_id' => $tenant->getKey()]);
    }

    /**
     * Global role (no tenant); `name` must stay unique with other global rows (tenant_id null).
     */
    public function global(): static
    {
        return $this->state(fn (): array => ['tenant_id' => null]);
    }
}

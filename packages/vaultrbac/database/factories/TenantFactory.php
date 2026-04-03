<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Database\Factories;

use Artwallet\VaultRbac\Enums\TenantStatus;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->company(),
            'status' => TenantStatus::Active,
            'settings' => null,
        ];
    }
}

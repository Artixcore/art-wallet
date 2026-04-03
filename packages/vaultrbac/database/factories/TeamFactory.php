<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Database\Factories;

use Artwallet\VaultRbac\Models\Team;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
final class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'company_id' => fn (array $attributes) => Branch::query()->findOrFail($attributes['branch_id'])->company_id,
            'name' => 'Table '.fake()->unique()->numberBetween(1, 999),
            'capacity' => fake()->numberBetween(2, 8),
        ];
    }
}

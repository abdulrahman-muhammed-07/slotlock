<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            // Keep company_id consistent with the parent venue.
            'company_id' => fn (array $attributes) => Venue::query()->findOrFail($attributes['venue_id'])->company_id,
            'name' => fake()->city().' Branch',
        ];
    }
}

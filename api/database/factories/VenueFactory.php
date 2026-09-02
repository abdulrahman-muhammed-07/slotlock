<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->streetName().' Venue',
        ];
    }
}

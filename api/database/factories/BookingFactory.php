<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $start = CarbonImmutable::parse(fake()->dateTimeBetween('+1 day', '+30 days'))
            ->setTime(fake()->numberBetween(9, 21), 0);

        return [
            'resource_id' => Resource::factory(),
            'branch_id' => fn (array $attributes) => Resource::query()->findOrFail($attributes['resource_id'])->branch_id,
            'company_id' => fn (array $attributes) => Resource::query()->findOrFail($attributes['resource_id'])->company_id,
            'customer_name' => fake()->name(),
            'slot_start' => $start,
            'slot_end' => $start->addHour(),
            'status' => 'confirmed',
        ];
    }

    public function forSlot(CarbonImmutable $start): static
    {
        return $this->state(fn (array $attributes) => [
            'slot_start' => $start,
            'slot_end' => $start->addHour(),
        ]);
    }
}

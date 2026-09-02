<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Resource;
use App\Models\User;
use App\Models\Venue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTenant('Northwind Hospitality', 'northwind-demo-token', 'owner@northwind.test');
        $this->seedTenant('Acme Reservations', 'acme-demo-token', 'owner@acme.test');
    }

    private function seedTenant(string $companyName, string $token, string $ownerEmail): void
    {
        $company = Company::factory()->create([
            'name' => $companyName,
            'api_token' => $token,
        ]);

        User::factory()->create([
            'company_id' => $company->id,
            'name' => $companyName.' Owner',
            'email' => $ownerEmail,
        ]);

        $venue = Venue::factory()->for($company)->create(['name' => $companyName.' Downtown']);
        $branch = Branch::factory()->for($company)->for($venue)->create(['name' => 'Main Floor']);

        $resources = Resource::factory()
            ->for($company)
            ->for($branch)
            ->count(4)
            ->sequence(
                ['name' => 'Table 1', 'capacity' => 2],
                ['name' => 'Table 2', 'capacity' => 2],
                ['name' => 'Table 3', 'capacity' => 4],
                ['name' => 'Table 4', 'capacity' => 6],
            )
            ->create();

        $slot = CarbonImmutable::today()->setTime(19, 0);

        Booking::factory()
            ->for($company)
            ->for($branch)
            ->for($resources->first(), 'resource')
            ->forSlot($slot)
            ->create(['customer_name' => 'Seeded Guest']);
    }
}

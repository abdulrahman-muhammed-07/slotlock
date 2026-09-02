<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Resource;
use App\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::store('redis')->flush();

    $this->company = Company::factory()->create();
    app(CurrentCompany::class)->set($this->company);

    $this->branch = Branch::factory()->for($this->company)->create();
    $this->resource = Resource::factory()->for($this->company)->for($this->branch)->create(['name' => 'Table 7']);
    $this->slot = CarbonImmutable::tomorrow()->setTime(18, 30);
});

function bookingPayload(int $resourceId, int $branchId, CarbonImmutable $slot): array
{
    return [
        'resource_id' => $resourceId,
        'branch_id' => $branchId,
        'customer_name' => 'Sam Rivera',
        'slot_start' => $slot->toIso8601String(),
        'slot_end' => $slot->addHour()->toIso8601String(),
    ];
}

it('lists the tenant resources', function (): void {
    $this->withHeaders(tokenFor($this->company))
        ->getJson('/api/resources')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Table 7');
});

it('creates a booking and returns 201 with the row', function (): void {
    $response = $this->withHeaders(tokenFor($this->company))
        ->postJson('/api/bookings', bookingPayload($this->resource->id, $this->branch->id, $this->slot));

    $response->assertCreated()
        ->assertJsonPath('data.customer_name', 'Sam Rivera')
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('bookings', [
        'resource_id' => $this->resource->id,
        'company_id' => $this->company->id,
        'slot_start' => $this->slot->format('Y-m-d H:i:s'),
    ]);
});

it('rejects a second booking for the same resource and slot with 409', function (): void {
    $payload = bookingPayload($this->resource->id, $this->branch->id, $this->slot);

    $this->withHeaders(tokenFor($this->company))->postJson('/api/bookings', $payload)->assertCreated();
    $this->withHeaders(tokenFor($this->company))->postJson('/api/bookings', $payload)->assertStatus(409);

    expect(Booking::query()->count())->toBe(1);
});

it('rejects booking a resource that is not in the given branch with 422', function (): void {
    $otherBranch = Branch::factory()->for($this->company)->create();

    $this->withHeaders(tokenFor($this->company))
        ->postJson('/api/bookings', bookingPayload($this->resource->id, $otherBranch->id, $this->slot))
        ->assertStatus(422);
});

it('validates the request shape', function (): void {
    $this->withHeaders(tokenFor($this->company))
        ->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'branch_id' => $this->branch->id,
            'customer_name' => '',
            'slot_start' => $this->slot->toIso8601String(),
            'slot_end' => $this->slot->subHour()->toIso8601String(),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['customer_name', 'slot_end']);
});

it('reads a booking back by id', function (): void {
    $created = $this->withHeaders(tokenFor($this->company))
        ->postJson('/api/bookings', bookingPayload($this->resource->id, $this->branch->id, $this->slot))
        ->json('data.id');

    $this->withHeaders(tokenFor($this->company))
        ->getJson("/api/bookings/{$created}")
        ->assertOk()
        ->assertJsonPath('data.id', $created);
});

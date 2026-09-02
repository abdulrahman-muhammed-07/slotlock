<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Two tenants, A and B. A authenticates with its own token and must never be
 * able to see or touch anything owned by B, whichever endpoint it goes
 * through: the scoped collection, the scoped report, or a direct id.
 *
 * The report endpoint is the interesting one. On main it leans entirely on
 * the global CompanyScope and this test passes. On the branch
 * bug/unscoped-report that scope is dropped and this test fails, which is the
 * whole point of keeping that branch around.
 */
beforeEach(function (): void {
    $this->companyA = Company::factory()->create();
    $this->companyB = Company::factory()->create();

    $this->resourceB = Resource::factory()->for($this->companyB)->create();
    $this->bookingB = Booking::factory()
        ->for($this->companyB)
        ->for($this->resourceB, 'resource')
        ->for(Branch::query()->findOrFail($this->resourceB->branch_id))
        ->create();
});

it('excludes another tenant bookings from the scoped list', function (): void {
    Booking::factory()
        ->for($this->companyA)
        ->for(Resource::factory()->for($this->companyA), 'resource')
        ->create();

    $response = $this->withHeaders(tokenFor($this->companyA))->getJson('/api/bookings');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect(collect($response->json('data'))->pluck('id'))
        ->not->toContain($this->bookingB->id);
});

it('excludes another tenant bookings from the cross-branch report', function (): void {
    $response = $this->withHeaders(tokenFor($this->companyA))->getJson('/api/reports/bookings');

    $response->assertOk();
    expect($response->json('data'))->toBe([]);
})->group('idor');

it('returns 403 when reading another tenant booking by id', function (): void {
    $this->withHeaders(tokenFor($this->companyA))
        ->getJson("/api/bookings/{$this->bookingB->id}")
        ->assertForbidden();
});

it('returns 404 when reading another tenant resource availability by id', function (): void {
    $this->withHeaders(tokenFor($this->companyA))
        ->getJson("/api/resources/{$this->resourceB->id}/availability")
        ->assertNotFound();
});

it('returns 403 when booking against another tenant resource', function (): void {
    $slot = CarbonImmutable::tomorrow()->setTime(19, 0);

    $this->withHeaders(tokenFor($this->companyA))
        ->postJson('/api/bookings', [
            'resource_id' => $this->resourceB->id,
            'branch_id' => $this->resourceB->branch_id,
            'customer_name' => 'Mallory',
            'slot_start' => $slot->toIso8601String(),
            'slot_end' => $slot->addHour()->toIso8601String(),
        ])
        ->assertForbidden();

    expect(Booking::withoutGlobalScopes()->count())->toBe(1); // only B's seeded row
});

it('rejects a request with no token', function (): void {
    $this->getJson('/api/bookings')->assertUnauthorized();
});

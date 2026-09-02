<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\Resource;
use App\Queries\AvailabilityQuery;
use App\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * Cache-aside with explicit invalidation. A stale availability read after a
 * booking is the classic "why is it still showing free" bug, so this proves
 * the write path calls Cache::forget for the exact key rather than waiting
 * for a TTL.
 */
beforeEach(function (): void {
    Cache::store('redis')->flush(); // isolated on REDIS_CACHE_DB=15, see phpunit.xml

    $this->company = Company::factory()->create();
    app(CurrentCompany::class)->set($this->company);

    $this->branch = Branch::factory()->for($this->company)->create();
    $this->resource = Resource::factory()->for($this->company)->for($this->branch)->create();
    $this->date = CarbonImmutable::tomorrow();
});

it('shows a booking created after the first availability read', function (): void {
    $slot = $this->date->setTime(20, 0);

    $first = $this->withHeaders(tokenFor($this->company))
        ->getJson("/api/resources/{$this->resource->id}/availability?date={$this->date->toDateString()}");
    $first->assertOk();
    expect($first->json('booked_slots'))->toBe([]);

    // Cache is now populated for that key.
    $key = AvailabilityQuery::key($this->company->id, $this->resource->id, $this->date);
    expect(Cache::store('redis')->has($key))->toBeTrue();

    $this->withHeaders(tokenFor($this->company))
        ->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'branch_id' => $this->branch->id,
            'customer_name' => 'Dana',
            'slot_start' => $slot->toIso8601String(),
            'slot_end' => $slot->addHour()->toIso8601String(),
        ])
        ->assertCreated();

    // The write invalidated the key, not a TTL expiry.
    expect(Cache::store('redis')->has($key))->toBeFalse();

    $second = $this->withHeaders(tokenFor($this->company))
        ->getJson("/api/resources/{$this->resource->id}/availability?date={$this->date->toDateString()}");
    $second->assertOk();
    expect($second->json('booked_slots'))->toBe([$slot->toIso8601String()]);
});

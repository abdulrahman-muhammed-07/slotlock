<?php

declare(strict_types=1);

use App\DataTransferObjects\BookingData;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Resource;
use App\Services\BookingService;
use App\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Why this test uses two real MySQL connections
|--------------------------------------------------------------------------
|
| A single-threaded test cannot prove the lock works. If you call
| BookingService::book() twice in a row on one connection, the second call
| simply sees the first row and returns a clean 409 (the availability check),
| and the lock is never exercised. That test would still pass if you deleted
| the transaction and the FOR UPDATE entirely.
|
| To exercise the lock you need two sessions contending for the same row at
| the same time. Here connection 1 (default "mysql") opens a transaction,
| takes SELECT ... FOR UPDATE on the slot, inserts its booking, and holds the
| transaction open. Connection 2 ("mysql_2", same database) then runs the real
| service. Its own transaction hits the same slot row, waits on connection 1's
| lock, and fails with a lock-wait timeout, which the service maps to
| SlotUnavailableException. Exactly one booking row exists at the end.
|
| Both connections point at the same physical database (mtb_test); "mysql_2"
| exists only so the two code paths get genuinely separate sessions. This
| test does NOT use RefreshDatabase: that trait wraps each test in a
| transaction on one connection, which would hide connection 1's writes from
| connection 2 and make the race impossible to express.
|
*/

uses(DatabaseMigrations::class);

it('lets exactly one of two racing bookings win the slot', function (): void {
    $company = Company::factory()->create();
    app(CurrentCompany::class)->set($company);

    $branch = Branch::factory()->for($company)->create();
    $resource = Resource::factory()->for($company)->for($branch)->create();

    $slot = CarbonImmutable::tomorrow()->setTime(19, 0);
    $data = new BookingData(
        resourceId: $resource->id,
        branchId: $branch->id,
        customerName: 'Connection Two',
        slotStart: $slot,
        slotEnd: $slot->addHour(),
    );

    $c1 = DB::connection('mysql');
    $c1->beginTransaction();

    try {
        // Connection 1 holds the slot: FOR UPDATE, then an uncommitted insert.
        $c1->table('bookings')
            ->where('resource_id', $resource->id)
            ->where('slot_start', $slot->toDateTimeString())
            ->lockForUpdate()
            ->get();

        $c1->table('bookings')->insert([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'resource_id' => $resource->id,
            'customer_name' => 'Connection One',
            'slot_start' => $slot->toDateTimeString(),
            'slot_end' => $slot->addHour()->toDateTimeString(),
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Connection 2 runs the real service. Fail fast instead of the 50s default.
        DB::connection('mysql_2')->statement('SET SESSION innodb_lock_wait_timeout = 3');
        config()->set('database.default', 'mysql_2');

        $service = app(BookingService::class);

        expect(fn () => $service->book($data))
            ->toThrow(SlotUnavailableException::class);
    } finally {
        config()->set('database.default', 'mysql');
        $c1->commit();
    }

    expect(
        Booking::withoutGlobalScopes()->where('resource_id', $resource->id)->count()
    )->toBe(1);
});

<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\BookingData;
use App\Exceptions\CrossTenantException;
use App\Exceptions\ResourceNotInBranchException;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Queries\AvailabilityQuery;
use App\Repositories\BookingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

/**
 * The concurrency-safe reservation path.
 *
 * Two layers of defence against a double booking:
 *   1. Application: a transaction plus SELECT ... FOR UPDATE on the slot row
 *      (BookingRepository::slotIsHeld), so a second request blocks until the
 *      first commits, then sees the slot as taken.
 *   2. Database: unique(resource_id, slot_start). If anything ever slips past
 *      layer 1, the insert fails and we map it to the same 409.
 *
 * A single-threaded test cannot prove layer 1 works. See
 * tests/Feature/Booking/DoubleBookingRaceTest.php for the two-connection test.
 */
final class BookingService
{
    /** MySQL driver error codes that mean "someone else already has this slot". */
    private const CONFLICT_DRIVER_CODES = [1062, 1205, 1213]; // duplicate key, lock wait timeout, deadlock

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly BookingRepository $bookings,
        private readonly AvailabilityQuery $availability,
    ) {}

    public function book(BookingData $data): Booking
    {
        $resource = $this->bookings->resourceForBranch($data->resourceId, $data->branchId);

        if ($resource === null) {
            throw $this->resolveResourceFailure($data->resourceId, $data->branchId);
        }

        $booking = $this->reserveUnderLock($data);

        $this->availability->forget(
            $booking->company_id,
            $booking->resource_id,
            CarbonImmutable::parse($booking->slot_start),
        );

        return $booking;
    }

    /**
     * A null resource means one of: it is the caller's own but sits in another
     * branch (422), it belongs to another tenant (403), or it does not exist
     * (422, though the FormRequest normally catches that first).
     */
    private function resolveResourceFailure(int $resourceId, int $branchId): ResourceNotInBranchException|CrossTenantException
    {
        if ($this->bookings->resourceExistsForTenant($resourceId)) {
            return ResourceNotInBranchException::make($resourceId, $branchId);
        }

        if ($this->bookings->resourceExistsForAnyTenant($resourceId)) {
            return CrossTenantException::forResource($resourceId);
        }

        return ResourceNotInBranchException::make($resourceId, $branchId);
    }

    private function reserveUnderLock(BookingData $data): Booking
    {
        try {
            return $this->connection->transaction(function () use ($data): Booking {
                if ($this->bookings->slotIsHeld($data->resourceId, $data->slotStart)) {
                    throw SlotUnavailableException::forSlot(
                        $data->resourceId,
                        $data->slotStart->toIso8601String(),
                    );
                }

                return $this->bookings->create($data);
            });
        } catch (QueryException $e) {
            if ($this->isSlotConflict($e)) {
                throw SlotUnavailableException::forSlot(
                    $data->resourceId,
                    $data->slotStart->toIso8601String(),
                );
            }

            throw $e;
        }
    }

    private function isSlotConflict(QueryException $e): bool
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return in_array($driverCode, self::CONFLICT_DRIVER_CODES, true);
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DataTransferObjects\BookingData;
use App\Models\Booking;
use App\Models\Resource;
use Carbon\CarbonImmutable;

interface BookingRepository
{
    /**
     * The caller's own resource for the given branch, or null if it belongs
     * to a different branch. Tenant scoping is applied by the global scope.
     */
    public function resourceForBranch(int $resourceId, int $branchId): ?Resource;

    /** Scoped to the current tenant, ignoring the branch filter. */
    public function resourceExistsForTenant(int $resourceId): bool;

    /** Ignores tenant scoping. Used only to tell a 403 apart from a 422. */
    public function resourceExistsForAnyTenant(int $resourceId): bool;

    /**
     * SELECT ... FOR UPDATE on the (resource_id, slot_start) row. Must be
     * called inside a transaction; the lock is held until it commits.
     * Returns whether a booking already holds the slot.
     */
    public function slotIsHeld(int $resourceId, CarbonImmutable $slotStart): bool;

    public function create(BookingData $data): Booking;
}

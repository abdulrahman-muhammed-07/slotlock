<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Cross-branch bookings report for the current tenant.
 *
 * This query relies entirely on the global CompanyScope to stay tenant-safe.
 * The branch bug/unscoped-report drops that scope here on purpose so the
 * IDOR test in tests/Feature/TenantIsolation has something real to catch.
 */
final class ReportController extends Controller
{
    public function bookings(): AnonymousResourceCollection
    {
        $bookings = Booking::query()
            ->orderByDesc('slot_start')
            ->get();

        return BookingResource::collection($bookings);
    }
}

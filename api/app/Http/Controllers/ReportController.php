<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Scopes\CompanyScope;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Cross-branch bookings report.
 *
 * BUG (intentional, for the IDOR test): this drops the global CompanyScope,
 * so the report returns every tenant's bookings, not just the caller's. On
 * main this scope is left in place and TenantIsolationTest passes; here it
 * fails, which is the point of this branch.
 */
final class ReportController extends Controller
{
    public function bookings(): AnonymousResourceCollection
    {
        $bookings = Booking::query()
            ->withoutGlobalScope(CompanyScope::class)
            ->orderByDesc('slot_start')
            ->get();

        return BookingResource::collection($bookings);
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Exceptions\CrossTenantException;
use App\Models\Booking;
use App\Models\Scopes\CompanyScope;
use App\Repositories\BookingRepository;
use App\Repositories\EloquentBookingRepository;
use App\Tenancy\CurrentCompany;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One resolved tenant per request lifecycle.
        $this->app->scoped(CurrentCompany::class);

        $this->app->bind(BookingRepository::class, EloquentBookingRepository::class);

        // BookingService depends on the abstract connection, not the DB facade,
        // so a test can point it at a second connection to force a real race.
        $this->app->bind(ConnectionInterface::class, fn ($app) => $app['db']->connection());
    }

    public function boot(): void
    {
        // Resolve {booking} without the tenant scope so a cross-tenant id gets
        // a deliberate 403 instead of a 404 that hides whether it exists.
        // Same-tenant collection endpoints still lean on the global scope.
        Route::bind('booking', function (string $id): Booking {
            $booking = Booking::query()
                ->withoutGlobalScope(CompanyScope::class)
                ->findOrFail($id);

            $current = app(CurrentCompany::class);

            if ($current->isResolved() && $booking->company_id !== $current->id()) {
                throw CrossTenantException::forBooking($id);
            }

            return $booking;
        });
    }
}

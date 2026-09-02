# api

Laravel booking API. Multi-tenant isolation via a global Eloquent scope, a
concurrency-safe `BookingService`, and Redis cache-aside for availability reads.
See the [repository README](../README.md) for the full picture.

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve

composer test        # Pint + PHPStan level 6 + Pest (needs MySQL + Redis)
```

Layout:

| Path | What |
| --- | --- |
| `app/Http/Middleware/ResolveCompany.php` | token to `CurrentCompany` |
| `app/Models/Scopes/CompanyScope.php` | the global tenant filter |
| `app/Services/BookingService.php` | transaction + `lockForUpdate` + unique backstop |
| `app/Queries/AvailabilityQuery.php` | cache-aside with explicit `Cache::forget` |
| `tests/Feature/Booking/DoubleBookingRaceTest.php` | two-connection race |

# slotlock

Multi-tenant booking API with a concurrency-safe reservation path. Laravel plus a small Angular client.

[![CI](https://github.com/abdulrahman-muhammed-07/slotlock/actions/workflows/ci.yml/badge.svg)](https://github.com/abdulrahman-muhammed-07/slotlock/actions/workflows/ci.yml)

A reference implementation of three things a booking backend has to get right:
multi-tenant isolation, a concurrency-safe reservation path, and cache-aside
reads with explicit invalidation. It is a Laravel API with a small Angular
client that exercises it end to end. A worked example, not a framework: read it,
lift the parts you need.

## Why this exists

Booking systems fail in two boring, predictable ways. They leak data across
tenants (company A reads company B's reservations through an endpoint someone
forgot to scope), and they double-book a slot when two requests race for it.
This repo shows one clean way to prevent both, with tests that actually drive
the race rather than asserting around it.

## How it works

### Tenant isolation

`ResolveCompany` middleware turns a bearer token into the current `Company` and
binds it into a request-scoped `CurrentCompany`. A global Eloquent scope
(`CompanyScope`) then filters every query on a tenant-owned model by
`company_id`, and the `BelongsToCompany` trait stamps `company_id` on every
insert. Collection endpoints rely on the scope; direct `{booking}` id lookups
add an explicit ownership check so a cross-tenant id gets a deliberate 403
instead of a 404 that hides whether the record exists.

The scoped `{booking}` lookup and the report endpoint both back this up; see
`app/Http/Controllers/ReportController.php` and the `Route::bind` in
`app/Providers/AppServiceProvider.php`.

There is a branch, `bug/unscoped-report`, where the cross-branch report endpoint
drops the global scope. On `main` the endpoint is scoped and the isolation test
passes; check out that branch and the same test fails, which is the point of
keeping it.

### Double-booking prevention

`BookingService::book()` wraps the check-and-insert in a transaction and takes
`SELECT ... FOR UPDATE` on the `(resource_id, slot_start)` row, so a second
request blocks until the first commits and then sees the slot as taken. A
`unique(resource_id, slot_start)` constraint is the backstop: if anything ever
slips past the lock, the insert fails and is mapped to the same 409.

### Cache-aside with explicit invalidation

`AvailabilityQuery` reads booked slots through Redis with a short TTL. Every
booking write calls `Cache::forget` for the exact key rather than waiting for
the TTL to lapse, so a booking is visible on the next read immediately.

## The concurrency test

`api/tests/Feature/Booking/DoubleBookingRaceTest.php`

It opens **two real MySQL connections**. Connection 1 starts a transaction,
takes the row lock on the slot, inserts, and holds the transaction open.
Connection 2 runs the real `BookingService` against the same database on a
separate connection; its transaction contends for the same row, hits a
lock-wait timeout, and the service maps that to `SlotUnavailableException`.
Exactly one booking row exists at the end. A single-threaded test cannot prove
this: calling `book()` twice in a row never makes the two paths contend.

Run just this test:

```bash
cd api
vendor/bin/pest tests/Feature/Booking/DoubleBookingRaceTest.php
```

## Run

```bash
# 1. Back the stack with MySQL + Redis
docker compose up -d

# 2. API
cd api
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve            # http://localhost:8000

# 3. Web client (separate shell)
cd web
npm ci
npm start                    # http://localhost:4200
```

Seeded tokens: `northwind-demo-token` and `acme-demo-token`. Seeded logins:
`owner@northwind.test` / `owner@acme.test`, password `password`.

### Tests

```bash
cd api && composer test      # Pint + PHPStan (level 6) + Pest
cd web && npm run lint && npm run build
```

The Pest suite talks to a real MySQL database (`mtb_test`) and real Redis;
`docker compose up` provides both. CI runs the same on PHP 8.4.

## Stack

Laravel API (PHP 8.4, slim skeleton, MySQL 8, Redis, Pest, PHPStan/Larastan
level 6, Pint) + Angular standalone client (TypeScript, ESLint, Karma/Jasmine).

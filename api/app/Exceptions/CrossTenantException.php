<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * The caller referenced a record that exists, but belongs to another tenant.
 * Distinct from a 404 (scoped collections simply hide other tenants) and from
 * a 422 (a malformed-but-in-tenant request): this is an access violation.
 */
final class CrossTenantException extends DomainException
{
    public static function forResource(int $resourceId): self
    {
        return new self("Resource {$resourceId} belongs to another tenant.");
    }

    public static function forBooking(int|string $bookingId): self
    {
        return new self("Booking {$bookingId} belongs to another tenant.");
    }

    public function status(): int
    {
        return 403;
    }
}

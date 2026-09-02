<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * The requested slot was taken between the availability read and the write.
 * Raised from the locked section of BookingService, or mapped from the
 * unique(resource_id, slot_start) violation that backs it.
 */
final class SlotUnavailableException extends DomainException
{
    public static function forSlot(int $resourceId, string $slotStart): self
    {
        return new self("Resource {$resourceId} is already booked for {$slotStart}.");
    }

    public function status(): int
    {
        return 409;
    }
}

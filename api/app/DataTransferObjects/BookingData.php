<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * Validated, typed input for BookingService::book(). Built from the
 * FormRequest at the HTTP boundary; the service never sees the raw request.
 */
final readonly class BookingData
{
    public function __construct(
        public int $resourceId,
        public int $branchId,
        public string $customerName,
        public CarbonImmutable $slotStart,
        public CarbonImmutable $slotEnd,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            resourceId: (int) $input['resource_id'],
            branchId: (int) $input['branch_id'],
            customerName: (string) $input['customer_name'],
            slotStart: CarbonImmutable::parse((string) $input['slot_start']),
            slotEnd: CarbonImmutable::parse((string) $input['slot_end']),
        );
    }
}

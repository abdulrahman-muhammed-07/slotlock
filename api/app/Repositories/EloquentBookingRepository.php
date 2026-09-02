<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DataTransferObjects\BookingData;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\Scopes\CompanyScope;
use Carbon\CarbonImmutable;

final class EloquentBookingRepository implements BookingRepository
{
    public function resourceForBranch(int $resourceId, int $branchId): ?Resource
    {
        return Resource::query()
            ->whereKey($resourceId)
            ->where('branch_id', $branchId)
            ->first();
    }

    public function resourceExistsForTenant(int $resourceId): bool
    {
        return Resource::query()->whereKey($resourceId)->exists();
    }

    public function resourceExistsForAnyTenant(int $resourceId): bool
    {
        return Resource::query()
            ->withoutGlobalScope(CompanyScope::class)
            ->whereKey($resourceId)
            ->exists();
    }

    public function slotIsHeld(int $resourceId, CarbonImmutable $slotStart): bool
    {
        return Booking::query()
            ->where('resource_id', $resourceId)
            ->where('slot_start', $slotStart)
            ->lockForUpdate()
            ->exists();
    }

    public function create(BookingData $data): Booking
    {
        return Booking::create([
            'branch_id' => $data->branchId,
            'resource_id' => $data->resourceId,
            'customer_name' => $data->customerName,
            'slot_start' => $data->slotStart,
            'slot_end' => $data->slotEnd,
            'status' => 'confirmed',
        ]);
    }
}

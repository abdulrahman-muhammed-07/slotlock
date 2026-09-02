<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booking */
final class BookingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resource_id' => $this->resource_id,
            'branch_id' => $this->branch_id,
            'customer_name' => $this->customer_name,
            'slot_start' => $this->slot_start->toIso8601String(),
            'slot_end' => $this->slot_end->toIso8601String(),
            'status' => $this->status,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DataTransferObjects\BookingData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * HTTP-shape validation only. Whether the resource belongs to the branch is a
 * business rule and lives in BookingService, not here.
 */
final class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'resource_id' => ['required', 'integer', 'exists:resources,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'slot_start' => ['required', 'date'],
            'slot_end' => ['required', 'date', 'after:slot_start'],
        ];
    }

    public function toDto(): BookingData
    {
        return BookingData::fromArray($this->validated());
    }
}

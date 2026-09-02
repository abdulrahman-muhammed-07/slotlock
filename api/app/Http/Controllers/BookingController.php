<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BookingController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BookingResource::collection(
            Booking::query()->orderByDesc('slot_start')->get()
        );
    }

    public function show(Booking $booking): BookingResource
    {
        return BookingResource::make($booking);
    }

    public function store(StoreBookingRequest $request, BookingService $service): JsonResponse
    {
        $booking = $service->book($request->toDto());

        return BookingResource::make($booking)
            ->response()
            ->setStatusCode(201);
    }
}

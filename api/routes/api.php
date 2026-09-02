<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

// Everything past here needs a valid company token. The tenant middleware
// resolves the company and binds it for the global scope.
Route::middleware('tenant')->group(function (): void {
    Route::get('resources', [ResourceController::class, 'index']);
    Route::get('resources/{resource}/availability', [AvailabilityController::class, 'show']);

    Route::get('bookings', [BookingController::class, 'index']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('bookings/{booking}', [BookingController::class, 'show']);

    Route::get('reports/bookings', [ReportController::class, 'bookings']);
});

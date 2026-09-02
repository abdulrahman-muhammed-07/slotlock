<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Queries\AvailabilityQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AvailabilityController extends Controller
{
    public function show(Request $request, Resource $resource, AvailabilityQuery $availability): JsonResponse
    {
        $date = CarbonImmutable::parse((string) $request->query('date', 'today'));

        return response()->json([
            'resource_id' => $resource->id,
            'date' => $date->toDateString(),
            'booked_slots' => $availability->for($resource, $date),
        ]);
    }
}

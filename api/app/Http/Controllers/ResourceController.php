<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ResourceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ResourceResource::collection(
            Resource::query()->orderBy('name')->get()
        );
    }
}

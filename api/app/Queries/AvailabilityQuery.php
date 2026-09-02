<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Booking;
use App\Models\Resource;
use App\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Read model for "what is booked on this resource on this day".
 *
 * Cache-aside: the first read populates Redis, later reads hit Redis, and
 * every booking write calls forget() for the exact key. Invalidation is
 * explicit, not left to a TTL. The short TTL is only a safety net.
 */
final class AvailabilityQuery
{
    private const TTL_SECONDS = 300;

    public function __construct(
        private readonly Cache $cache,
        private readonly CurrentCompany $currentCompany,
    ) {}

    /**
     * @return list<string> booked slot_start times, ISO-8601, ascending
     */
    public function for(Resource $resource, CarbonImmutable $date): array
    {
        $key = self::key($this->currentCompany->id(), $resource->getKey(), $date);

        return $this->cache->remember($key, self::TTL_SECONDS, function () use ($resource, $date): array {
            return Booking::query()
                ->where('resource_id', $resource->getKey())
                ->whereBetween('slot_start', [$date->startOfDay(), $date->endOfDay()])
                ->orderBy('slot_start')
                ->pluck('slot_start')
                ->map(fn ($slot): string => CarbonImmutable::parse($slot)->toIso8601String())
                ->all();
        });
    }

    public function forget(int $companyId, int $resourceId, CarbonImmutable $date): void
    {
        $this->cache->forget(self::key($companyId, $resourceId, $date));
    }

    public static function key(int $companyId, int $resourceId, CarbonImmutable $date): string
    {
        return sprintf('availability:%d:%d:%s', $companyId, $resourceId, $date->toDateString());
    }
}

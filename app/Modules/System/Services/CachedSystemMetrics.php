<?php

namespace App\Modules\System\Services;

use App\Modules\System\Contracts\SystemMetrics;
use App\Modules\System\Data\SystemSnapshot;
use Illuminate\Contracts\Cache\Repository;

/**
 * Keeps polling from re-reading the host on every request.
 *
 * @phpstan-import-type SystemSnapshotArray from SystemSnapshot
 */
final readonly class CachedSystemMetrics implements SystemMetrics
{
    public const string CACHE_KEY = 'smartflat.system.snapshot';

    public function __construct(
        private SystemMetrics $metrics,
        private Repository $cache,
        private int $seconds = 2,
    ) {}

    /**
     * Only primitives are cached: stores such as Redis are configured to refuse
     * unserializing classes (`cache.serializable_classes`), so a cached object
     * would silently come back as `__PHP_Incomplete_Class`.
     */
    public function snapshot(): SystemSnapshot
    {
        /** @var SystemSnapshotArray $payload */
        $payload = $this->cache->remember(
            self::CACHE_KEY,
            $this->seconds,
            fn (): array => $this->metrics->snapshot()->toArray(),
        );

        return SystemSnapshot::fromArray($payload);
    }
}

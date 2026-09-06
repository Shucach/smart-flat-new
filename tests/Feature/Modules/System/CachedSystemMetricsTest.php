<?php

use App\Modules\System\Contracts\SystemMetrics;
use App\Modules\System\Data\SystemSnapshot;
use App\Modules\System\Services\CachedSystemMetrics;
use App\Modules\System\Services\NullSystemMetrics;
use Illuminate\Support\Facades\Cache;

function countingSystemMetrics(): SystemMetrics
{
    return new class implements SystemMetrics
    {
        public int $calls = 0;

        public function snapshot(): SystemSnapshot
        {
            $this->calls++;

            return (new NullSystemMetrics)->snapshot();
        }
    };
}

it('reads the host only once while the snapshot is fresh', function () {
    $inner = countingSystemMetrics();
    $metrics = new CachedSystemMetrics($inner, Cache::store(), 2);

    $metrics->snapshot();
    $metrics->snapshot();

    expect($inner->calls)->toBe(1);
});

it('reads the host again once the snapshot expired', function () {
    $inner = countingSystemMetrics();
    $metrics = new CachedSystemMetrics($inner, Cache::store(), 2);

    $metrics->snapshot();
    $this->travel(3)->seconds();
    $metrics->snapshot();

    expect($inner->calls)->toBe(2);
});

it('resolves the metrics contract through the caching decorator', function () {
    expect($this->app->make(SystemMetrics::class))->toBeInstanceOf(CachedSystemMetrics::class);
});

it('caches primitives so stores that refuse to unserialize classes stay usable', function () {
    $metrics = new CachedSystemMetrics(countingSystemMetrics(), Cache::store(), 2);

    $metrics->snapshot();

    $cached = Cache::store()->get(CachedSystemMetrics::CACHE_KEY);

    expect($cached)->toBeArray()
        ->and(unserialize(serialize($cached), ['allowed_classes' => false]))->toBe($cached);
});

it('rebuilds a full snapshot from the cached payload', function () {
    $inner = countingSystemMetrics();
    $metrics = new CachedSystemMetrics($inner, Cache::store(), 2);

    $fresh = $metrics->snapshot();
    $fromCache = $metrics->snapshot();

    expect($inner->calls)->toBe(1)
        ->and($fromCache)->toBeInstanceOf(SystemSnapshot::class)
        ->and($fromCache->toArray())->toBe($fresh->toArray());
});

<?php

use App\Modules\System\Support\ProcMeminfoParser;

it('converts the kibibyte counters into bytes', function () {
    $memory = (new ProcMeminfoParser)->parse(
        file_get_contents(__DIR__.'/../../../../Fixtures/System/proc_meminfo.txt')
    );

    expect($memory->totalBytes)->toBe(4_142_530_560)
        ->and($memory->freeBytes)->toBe(3_106_406_400)
        ->and($memory->usedBytes)->toBe(1_036_124_160)
        ->and($memory->swapTotalBytes)->toBe(1_073_741_824)
        ->and($memory->swapUsedBytes)->toBe(268_435_456)
        ->and(round($memory->usagePercent(), 1))->toBe(25.0);
});

it('derives the available memory from free, buffers and cache when MemAvailable is absent', function () {
    $memory = (new ProcMeminfoParser)->parse(<<<'TXT'
    MemTotal:        1024 kB
    MemFree:          256 kB
    Buffers:          128 kB
    Cached:           128 kB
    TXT);

    expect($memory->freeBytes)->toBe(512 * 1024)
        ->and($memory->usedBytes)->toBe(512 * 1024);
});

it('returns an empty snapshot for unparseable input', function () {
    $memory = (new ProcMeminfoParser)->parse('');

    expect($memory->totalBytes)->toBe(0)
        ->and($memory->usagePercent())->toBe(0.0);
});

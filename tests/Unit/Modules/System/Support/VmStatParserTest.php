<?php

use App\Modules\System\Support\VmStatParser;

it('converts the reported pages into bytes using the reported page size', function () {
    $memory = (new VmStatParser)->parse(
        file_get_contents(__DIR__.'/../../../../Fixtures/System/vm_stat.txt'),
        totalBytes: 8_589_934_592,
        swapTotalBytes: 1_073_741_824,
        swapUsedBytes: 268_435_456,
    );

    expect($memory->totalBytes)->toBe(8_589_934_592)
        ->and($memory->freeBytes)->toBe(2_457_600_000)
        ->and($memory->usedBytes)->toBe(6_132_334_592)
        ->and($memory->swapUsedBytes)->toBe(268_435_456);
});

it('never reports more free memory than the machine has', function () {
    $memory = (new VmStatParser)->parse(
        file_get_contents(__DIR__.'/../../../../Fixtures/System/vm_stat.txt'),
        totalBytes: 1_000_000,
    );

    expect($memory->freeBytes)->toBe(1_000_000)
        ->and($memory->usedBytes)->toBe(0);
});

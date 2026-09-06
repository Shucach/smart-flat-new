<?php

use App\Modules\System\Support\PsCpuParser;

it('averages the process shares over the available cores', function () {
    $usage = (new PsCpuParser)->usagePercent(
        file_get_contents(__DIR__.'/../../../../Fixtures/System/ps_cpu.txt'),
        cores: 4,
    );

    expect($usage)->toBe(10.0);
});

it('caps the usage at one hundred percent', function () {
    expect((new PsCpuParser)->usagePercent("180.0\n90.0", cores: 1))->toBe(100.0);
});

<?php

use App\Modules\System\Support\ProcStatParser;

function procStatFixture(string $name): string
{
    return file_get_contents(__DIR__.'/../../../../Fixtures/System/'.$name);
}

it('reports the busy share between two samples', function () {
    $usage = (new ProcStatParser)->usagePercent(
        procStatFixture('proc_stat_first.txt'),
        procStatFixture('proc_stat_second.txt'),
    );

    expect($usage)->toBe(20.0);
});

it('returns zero when both samples are identical', function () {
    $sample = procStatFixture('proc_stat_first.txt');

    expect((new ProcStatParser)->usagePercent($sample, $sample))->toBe(0.0);
});

it('counts the per core lines', function () {
    expect((new ProcStatParser)->coreCount(procStatFixture('proc_stat_first.txt')))->toBe(4);
});

it('falls back to a single core when the file is empty', function () {
    expect((new ProcStatParser)->coreCount(''))->toBe(1);
});

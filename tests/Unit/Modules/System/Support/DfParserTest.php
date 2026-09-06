<?php

use App\Modules\System\Support\DfParser;

it('parses the 1024 byte blocks of df into bytes', function () {
    $disks = (new DfParser)->parse(
        file_get_contents(__DIR__.'/../../../../Fixtures/System/df_kp.txt'),
        ['/dev/sdb3' => 'Медіа'],
    );

    expect($disks)->toHaveCount(3)
        ->and($disks[0]->device)->toBe('/dev/root')
        ->and($disks[0]->mountPoint)->toBe('/')
        ->and($disks[0]->label)->toBe('/')
        ->and($disks[0]->totalBytes)->toBe(31_025_332_224)
        ->and($disks[0]->freeBytes)->toBe(17_542_047_744)
        ->and(round($disks[0]->usagePercent(), 1))->toBe(40.0);
});

it('keeps a mount point that contains spaces', function () {
    $disks = (new DfParser)->parse(
        file_get_contents(__DIR__.'/../../../../Fixtures/System/df_kp.txt'),
        ['/dev/sdb3' => 'Медіа'],
    );

    expect($disks[2]->mountPoint)->toBe('/media/nfs data')
        ->and($disks[2]->label)->toBe('Медіа');
});

it('skips pseudo filesystems that are not backed by a device', function () {
    $disks = (new DfParser)->parse(
        file_get_contents(__DIR__.'/../../../../Fixtures/System/df_kp.txt')
    );

    expect(array_map(fn ($disk) => $disk->device, $disks))
        ->not->toContain('tmpfs')
        ->not->toContain('devtmpfs');
});

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

describe('inside the container the app is deployed to', function () {
    $parse = fn (array $labels = []): array => (new DfParser)->parse(
        file_get_contents(__DIR__.'/../../../../Fixtures/System/df_kp_container.txt'),
        $labels,
    );

    it('keeps the NFS shares the media library lives on', function () use ($parse) {
        $disks = $parse();

        expect(array_map(fn ($disk) => $disk->mountPoint, $disks))
            ->toBe(['/', '/media/nfs', '/media/cloud/cloud']);
    });

    it('reads the size of an NFS share', function () use ($parse) {
        $media = $parse()[1];

        expect($media->device)->toBe('192.168.0.39:/export/media')
            ->and($media->totalBytes)->toBe(1_967_846_326_272)
            ->and($media->usedBytes)->toBe(1_051_738_767_360)
            ->and($media->freeBytes)->toBe(916_090_781_696)
            ->and(round($media->usagePercent(), 1))->toBe(53.4);
    });

    it('keeps the overlay root the container runs on', function () use ($parse) {
        expect($parse()[0]->device)->toBe('overlay');
    });

    it('drops the single files docker binds off the host disk', function () use ($parse) {
        expect(array_map(fn ($disk) => $disk->mountPoint, $parse()))
            ->not->toContain('/etc/hosts')
            ->not->toContain('/proc/asound');
    });

    it('labels an NFS share by its device', function () use ($parse) {
        expect($parse(['192.168.0.39:/export/media' => 'Медіа'])[1]->label)->toBe('Медіа');
    });
});

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Library
    |--------------------------------------------------------------------------
    |
    | The media library browses a directory on the same machine that runs the
    | application. Everything outside of the configured root is unreachable.
    |
    */

    'media' => [
        'root' => env('SMARTFLAT_MEDIA_ROOT', '/media/nfs'),
        'ignored_extensions' => ['torrent', 'log', 'aria2'],
    ],

    /*
    |--------------------------------------------------------------------------
    | System
    |--------------------------------------------------------------------------
    |
    | Metrics are read from the host the application runs on. The power driver
    | performs reboot and shutdown of that very same host.
    |
    */

    'system' => [
        'driver' => env('SMARTFLAT_SYSTEM_DRIVER', 'auto'),
        'power_driver' => env('SMARTFLAT_POWER_DRIVER', 'null'),
        'reboot_command' => env('SMARTFLAT_REBOOT_COMMAND', '/usr/bin/sudo /sbin/reboot'),
        'shutdown_command' => env('SMARTFLAT_SHUTDOWN_COMMAND', '/usr/bin/sudo /sbin/shutdown -h now'),
        'disk_labels' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Smart Frame
    |--------------------------------------------------------------------------
    |
    | The smart frame exposes a small HTTP API for listing, uploading and
    | deleting the images it shows.
    |
    */

    'frame' => [
        'driver' => env('SMARTFLAT_FRAME_DRIVER', 'http'),
        'host' => env('SMART_FRAME_HOST'),
        'key' => env('SMART_FRAME_KEY'),
        'timeout' => env('SMART_FRAME_TIMEOUT', 15),
    ],

];

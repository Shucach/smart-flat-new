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

        /*
        | Clips are converted here, on the queue, because the frame decodes H.264
        | only and cannot re-encode anything itself. The size below is the panel's
        | own: 1024x600 turned on its side.
        */
        'video' => [
            'driver' => env('SMARTFLAT_FRAME_VIDEO_DRIVER', 'ffmpeg'),
            'ffmpeg' => env('FFMPEG_BIN', 'ffmpeg'),
            'ffprobe' => env('FFPROBE_BIN', 'ffprobe'),
            'width' => env('SMART_FRAME_VIDEO_WIDTH', 600),
            'height' => env('SMART_FRAME_VIDEO_HEIGHT', 1024),
            'frame_rate' => env('SMART_FRAME_VIDEO_FPS', 25),
            'bitrate' => env('SMART_FRAME_VIDEO_BITRATE', '2M'),
            'max_seconds' => env('SMART_FRAME_VIDEO_MAX_SECONDS', 60),
            'max_upload_kilobytes' => env('SMART_FRAME_VIDEO_MAX_KILOBYTES', 204800),
            'timeout' => env('SMART_FRAME_VIDEO_TIMEOUT', 1800),
        ],
    ],

];

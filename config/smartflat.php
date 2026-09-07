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
    | `shell` runs the commands below and only works where the application owns
    | the machine. Production runs in a container, which cannot power the board
    | off however it is configured, so it uses `request-file`: the request is
    | written to a directory the host watches, and a unit there does the work.
    |
    */

    'system' => [
        'driver' => env('SMARTFLAT_SYSTEM_DRIVER', 'auto'),
        'power_driver' => env('SMARTFLAT_POWER_DRIVER', 'null'),
        'reboot_command' => env('SMARTFLAT_REBOOT_COMMAND', '/usr/bin/sudo /sbin/reboot'),
        'shutdown_command' => env('SMARTFLAT_SHUTDOWN_COMMAND', '/usr/bin/sudo /sbin/shutdown -h now'),
        'power_request_path' => env('SMARTFLAT_POWER_REQUEST_PATH', storage_path('app/power')),
        'disk_labels' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Torrents
    |--------------------------------------------------------------------------
    |
    | Transmission is driven through its JSON-RPC endpoint. The credentials are
    | the ones the daemon was started with -- the linuxserver image turns
    | authentication on as soon as it is given a user and a password.
    |
    | `max_file_kilobytes` caps an uploaded .torrent file; the metainfo travels
    | to the daemon base64 encoded, so it is held in memory while it does.
    |
    */

    'torrent' => [
        'driver' => env('SMARTFLAT_TORRENT_DRIVER', 'transmission'),
        'url' => env('TRANSMISSION_RPC_URL', 'http://127.0.0.1:9091/transmission/rpc'),
        'username' => env('TRANSMISSION_RPC_USERNAME', ''),
        'password' => env('TRANSMISSION_RPC_PASSWORD', ''),
        'timeout' => env('TRANSMISSION_RPC_TIMEOUT', 10),
        'max_file_kilobytes' => env('TRANSMISSION_MAX_TORRENT_KILOBYTES', 5120),
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

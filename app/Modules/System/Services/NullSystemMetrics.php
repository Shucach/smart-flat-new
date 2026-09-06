<?php

namespace App\Modules\System\Services;

use App\Modules\System\Contracts\SystemMetrics;
use App\Modules\System\Data\CpuLoad;
use App\Modules\System\Data\DiskUsage;
use App\Modules\System\Data\HostInfo;
use App\Modules\System\Data\MemoryUsage;
use App\Modules\System\Data\SystemSnapshot;
use Illuminate\Support\Carbon;

/**
 * A deterministic snapshot used when no host metrics are available.
 */
final readonly class NullSystemMetrics implements SystemMetrics
{
    private const int UPTIME_SECONDS = 93_784;

    private const int TOTAL_MEMORY = 4 * 1024 * 1024 * 1024;

    public function snapshot(): SystemSnapshot
    {
        return new SystemSnapshot(
            host: new HostInfo(
                name: 'smartflat',
                os: 'SmartFlat OS 1.0',
                uptimeSeconds: self::UPTIME_SECONDS,
                bootedAt: Carbon::now()->subSeconds(self::UPTIME_SECONDS),
            ),
            cpu: new CpuLoad(
                usagePercent: 23.5,
                cores: 4,
                loadAverage: [0.42, 0.35, 0.28],
                temperatureCelsius: 47.2,
            ),
            memory: new MemoryUsage(
                totalBytes: self::TOTAL_MEMORY,
                usedBytes: (int) (self::TOTAL_MEMORY * 0.45),
                freeBytes: self::TOTAL_MEMORY - (int) (self::TOTAL_MEMORY * 0.45),
                swapTotalBytes: 1024 * 1024 * 1024,
                swapUsedBytes: 128 * 1024 * 1024,
            ),
            disks: [
                new DiskUsage(
                    device: '/dev/sda1',
                    mountPoint: '/',
                    label: 'Система',
                    totalBytes: 32 * 1024 * 1024 * 1024,
                    usedBytes: 12 * 1024 * 1024 * 1024,
                    freeBytes: 20 * 1024 * 1024 * 1024,
                ),
                new DiskUsage(
                    device: '/dev/sdb1',
                    mountPoint: '/media/nfs',
                    label: 'Медіа',
                    totalBytes: 2000 * 1024 * 1024 * 1024,
                    usedBytes: 1400 * 1024 * 1024 * 1024,
                    freeBytes: 600 * 1024 * 1024 * 1024,
                ),
            ],
            capturedAt: Carbon::now(),
        );
    }
}

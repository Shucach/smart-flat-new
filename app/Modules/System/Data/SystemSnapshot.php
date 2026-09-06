<?php

namespace App\Modules\System\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * @phpstan-type SystemSnapshotArray array{
 *     host: array{name: string, os: string, uptimeSeconds: int, bootedAt: string},
 *     cpu: array{usagePercent: float, cores: int, loadAverage: array{0: float, 1: float, 2: float}, temperatureCelsius: float|null},
 *     memory: array{totalBytes: int, usedBytes: int, freeBytes: int, usagePercent: float, swapTotalBytes: int, swapUsedBytes: int},
 *     disks: array<int, array{device: string, mountPoint: string, label: string, totalBytes: int, usedBytes: int, freeBytes: int, usagePercent: float}>,
 *     capturedAt: string,
 * }
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class SystemSnapshot implements Arrayable
{
    /**
     * @param  array<int, DiskUsage>  $disks
     */
    public function __construct(
        public HostInfo $host,
        public CpuLoad $cpu,
        public MemoryUsage $memory,
        public array $disks,
        public Carbon $capturedAt,
    ) {}

    /**
     * @param  SystemSnapshotArray  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            host: HostInfo::fromArray($data['host']),
            cpu: CpuLoad::fromArray($data['cpu']),
            memory: MemoryUsage::fromArray($data['memory']),
            disks: array_map(
                static fn (array $disk): DiskUsage => DiskUsage::fromArray($disk),
                $data['disks'],
            ),
            capturedAt: Carbon::parse($data['capturedAt']),
        );
    }

    /**
     * @return SystemSnapshotArray
     */
    public function toArray(): array
    {
        return [
            'host' => $this->host->toArray(),
            'cpu' => $this->cpu->toArray(),
            'memory' => $this->memory->toArray(),
            'disks' => array_map(static fn (DiskUsage $disk): array => $disk->toArray(), $this->disks),
            'capturedAt' => $this->capturedAt->toIso8601String(),
        ];
    }
}

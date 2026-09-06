<?php

namespace App\Modules\System\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class DiskUsage implements Arrayable
{
    public function __construct(
        public string $device,
        public string $mountPoint,
        public string $label,
        public int $totalBytes,
        public int $usedBytes,
        public int $freeBytes,
    ) {}

    public function usagePercent(): float
    {
        return $this->totalBytes > 0 ? $this->usedBytes / $this->totalBytes * 100 : 0.0;
    }

    /**
     * @param  array{device: string, mountPoint: string, label: string, totalBytes: int, usedBytes: int, freeBytes: int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            device: (string) $data['device'],
            mountPoint: (string) $data['mountPoint'],
            label: (string) $data['label'],
            totalBytes: (int) $data['totalBytes'],
            usedBytes: (int) $data['usedBytes'],
            freeBytes: (int) $data['freeBytes'],
        );
    }

    /**
     * @return array{device: string, mountPoint: string, label: string, totalBytes: int, usedBytes: int, freeBytes: int, usagePercent: float}
     */
    public function toArray(): array
    {
        return [
            'device' => $this->device,
            'mountPoint' => $this->mountPoint,
            'label' => $this->label,
            'totalBytes' => $this->totalBytes,
            'usedBytes' => $this->usedBytes,
            'freeBytes' => $this->freeBytes,
            'usagePercent' => round($this->usagePercent(), 1),
        ];
    }
}

<?php

namespace App\Modules\System\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class MemoryUsage implements Arrayable
{
    public function __construct(
        public int $totalBytes,
        public int $usedBytes,
        public int $freeBytes,
        public int $swapTotalBytes,
        public int $swapUsedBytes,
    ) {}

    public function usagePercent(): float
    {
        return $this->totalBytes > 0 ? $this->usedBytes / $this->totalBytes * 100 : 0.0;
    }

    /**
     * @param  array{totalBytes: int, usedBytes: int, freeBytes: int, swapTotalBytes: int, swapUsedBytes: int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            totalBytes: (int) $data['totalBytes'],
            usedBytes: (int) $data['usedBytes'],
            freeBytes: (int) $data['freeBytes'],
            swapTotalBytes: (int) $data['swapTotalBytes'],
            swapUsedBytes: (int) $data['swapUsedBytes'],
        );
    }

    /**
     * @return array{totalBytes: int, usedBytes: int, freeBytes: int, usagePercent: float, swapTotalBytes: int, swapUsedBytes: int}
     */
    public function toArray(): array
    {
        return [
            'totalBytes' => $this->totalBytes,
            'usedBytes' => $this->usedBytes,
            'freeBytes' => $this->freeBytes,
            'usagePercent' => round($this->usagePercent(), 1),
            'swapTotalBytes' => $this->swapTotalBytes,
            'swapUsedBytes' => $this->swapUsedBytes,
        ];
    }
}

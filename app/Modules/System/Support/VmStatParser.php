<?php

namespace App\Modules\System\Support;

use App\Modules\System\Data\MemoryUsage;

/**
 * Parses the macOS `vm_stat` page counters into a memory usage snapshot.
 */
final class VmStatParser
{
    /**
     * @param  int  $totalBytes  Physical memory as reported by `sysctl -n hw.memsize`.
     */
    public function parse(string $output, int $totalBytes, int $swapTotalBytes = 0, int $swapUsedBytes = 0): MemoryUsage
    {
        $pageSize = 4096;

        if (preg_match('/page size of (\d+) bytes/', $output, $matches) === 1) {
            $pageSize = (int) $matches[1];
        }

        $pages = [];

        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            if (preg_match('/^"?([^":]+)"?:\s+(\d+)\.?$/', trim($line), $matches) === 1) {
                $pages[trim($matches[1])] = (int) $matches[2];
            }
        }

        $free = (($pages['Pages free'] ?? 0) + ($pages['Pages speculative'] ?? 0)) * $pageSize;
        $free = min($free, $totalBytes);

        return new MemoryUsage(
            totalBytes: $totalBytes,
            usedBytes: max(0, $totalBytes - $free),
            freeBytes: $free,
            swapTotalBytes: $swapTotalBytes,
            swapUsedBytes: $swapUsedBytes,
        );
    }
}

<?php

namespace App\Modules\System\Support;

use App\Modules\System\Data\MemoryUsage;

/**
 * Parses `/proc/meminfo`, whose values are reported in kibibytes.
 */
final class ProcMeminfoParser
{
    public function parse(string $contents): MemoryUsage
    {
        $values = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            if (preg_match('/^(\w+):\s+(\d+)(?:\s+kB)?$/', trim($line), $matches) === 1) {
                $values[$matches[1]] = (int) $matches[2] * 1024;
            }
        }

        $total = $values['MemTotal'] ?? 0;
        $available = $values['MemAvailable']
            ?? (($values['MemFree'] ?? 0) + ($values['Buffers'] ?? 0) + ($values['Cached'] ?? 0));
        $available = min($available, $total);

        $swapTotal = $values['SwapTotal'] ?? 0;

        return new MemoryUsage(
            totalBytes: $total,
            usedBytes: max(0, $total - $available),
            freeBytes: $available,
            swapTotalBytes: $swapTotal,
            swapUsedBytes: max(0, $swapTotal - ($values['SwapFree'] ?? 0)),
        );
    }
}

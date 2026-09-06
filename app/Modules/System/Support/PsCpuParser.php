<?php

namespace App\Modules\System\Support;

/**
 * Sums the per process CPU shares reported by `ps -A -o %cpu=`.
 */
final class PsCpuParser
{
    public function usagePercent(string $output, int $cores): float
    {
        $total = 0.0;

        foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
            if (is_numeric(trim($line))) {
                $total += (float) trim($line);
            }
        }

        return max(0.0, min(100.0, $total / max(1, $cores)));
    }
}

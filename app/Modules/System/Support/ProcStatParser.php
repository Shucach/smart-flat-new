<?php

namespace App\Modules\System\Support;

/**
 * Parses the aggregated CPU line and the per core lines of `/proc/stat`.
 */
final class ProcStatParser
{
    /**
     * The cumulative jiffies of the aggregated `cpu` line.
     *
     * @return array{total: int, idle: int}
     */
    public function totals(string $contents): array
    {
        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            if (! str_starts_with($line, 'cpu ')) {
                continue;
            }

            $values = array_map(intval(...), preg_split('/\s+/', trim(substr($line, 4))) ?: []);

            return [
                'total' => array_sum($values),
                'idle' => ($values[3] ?? 0) + ($values[4] ?? 0),
            ];
        }

        return ['total' => 0, 'idle' => 0];
    }

    /**
     * The busy share between two `/proc/stat` samples, in percent.
     */
    public function usagePercent(string $first, string $second): float
    {
        $before = $this->totals($first);
        $after = $this->totals($second);

        $totalDelta = $after['total'] - $before['total'];
        $idleDelta = $after['idle'] - $before['idle'];

        if ($totalDelta <= 0) {
            return 0.0;
        }

        return max(0.0, min(100.0, ($totalDelta - $idleDelta) / $totalDelta * 100));
    }

    public function coreCount(string $contents): int
    {
        preg_match_all('/^cpu\d+\s/m', $contents, $matches);

        return max(1, count($matches[0]));
    }
}

<?php

namespace App\Modules\System\Support;

use App\Modules\System\Data\DiskUsage;

/**
 * Parses the POSIX output of `df -kP`, whose blocks are 1024 bytes wide.
 */
final class DfParser
{
    private const int BLOCK_SIZE = 1024;

    /**
     * @param  array<string, string>  $labels  Device or mount point to display label.
     * @return array<int, DiskUsage>
     */
    public function parse(string $output, array $labels = []): array
    {
        $disks = [];
        $seen = [];

        foreach (preg_split('/\R/', trim($output)) ?: [] as $index => $line) {
            if ($index === 0 || trim($line) === '') {
                continue;
            }

            $columns = preg_split('/\s+/', trim($line), 6);

            if ($columns === false || count($columns) < 6) {
                continue;
            }

            [$device, $blocks, $used, $available, , $mountPoint] = $columns;

            if (! str_starts_with($device, '/') || isset($seen[$mountPoint])) {
                continue;
            }

            $seen[$mountPoint] = true;

            $disks[] = new DiskUsage(
                device: $device,
                mountPoint: $mountPoint,
                label: $labels[$device] ?? $labels[$mountPoint] ?? $mountPoint,
                totalBytes: (int) $blocks * self::BLOCK_SIZE,
                usedBytes: (int) $used * self::BLOCK_SIZE,
                freeBytes: (int) $available * self::BLOCK_SIZE,
            );
        }

        return $disks;
    }
}

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
     * Devices backed by memory or by the kernel. They report a size, but none
     * of them is a disk anybody wants to watch fill up.
     *
     * @var array<int, string>
     */
    private const array PSEUDO_DEVICES = [
        'tmpfs', 'devtmpfs', 'udev', 'shm', 'none', 'proc', 'sysfs', 'cgroup',
        'cgroup2', 'devpts', 'ramfs', 'efivarfs', 'tracefs', 'debugfs',
        'securityfs', 'pstore', 'fusectl', 'configfs', 'mqueue', 'hugetlbfs',
        'binfmt_misc', 'squashfs', 'nsfs', 'overlayfs',
    ];

    /**
     * The application runs inside a container, where Docker binds single files
     * such as /etc/hosts off the host disk and df reports each of them as a
     * whole filesystem. These roots drop those, along with the kernel's own
     * mount points.
     *
     * @var array<int, string>
     */
    private const array IGNORED_MOUNT_ROOTS = [
        '/dev', '/proc', '/sys', '/run', '/snap', '/etc', '/var/lib/docker',
    ];

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

            if (! $this->isRealStorage($device, $mountPoint) || isset($seen[$mountPoint])) {
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

    /**
     * A device is kept when it is a block device (/dev/sda1), a network share
     * (192.168.0.39:/export/media, //server/share) or the container's own
     * overlay root -- the three kinds that hold files somebody put there.
     */
    private function isRealStorage(string $device, string $mountPoint): bool
    {
        if (in_array($device, self::PSEUDO_DEVICES, true)) {
            return false;
        }

        foreach (self::IGNORED_MOUNT_ROOTS as $root) {
            if ($mountPoint === $root || str_starts_with($mountPoint, $root.'/')) {
                return false;
            }
        }

        return str_starts_with($device, '/')
            || str_contains($device, ':/')
            || $device === 'overlay';
    }
}

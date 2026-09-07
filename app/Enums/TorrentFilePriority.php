<?php

namespace App\Enums;

enum TorrentFilePriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';

    public static function fromRpc(int $priority): self
    {
        return match ($priority) {
            -1 => self::Low,
            1 => self::High,
            default => self::Normal,
        };
    }

    /**
     * The `torrent-set` argument that moves files into this priority.
     */
    public function rpcArgument(): string
    {
        return match ($this) {
            self::Low => 'priority-low',
            self::Normal => 'priority-normal',
            self::High => 'priority-high',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Низький',
            self::Normal => 'Звичайний',
            self::High => 'Високий',
        };
    }
}

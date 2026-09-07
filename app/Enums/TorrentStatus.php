<?php

namespace App\Enums;

/**
 * The seven states Transmission reports through the `status` field of
 * `torrent-get`, named rather than numbered so the page can read them.
 */
enum TorrentStatus: string
{
    case Stopped = 'stopped';
    case CheckWait = 'check-wait';
    case Checking = 'checking';
    case DownloadWait = 'download-wait';
    case Downloading = 'downloading';
    case SeedWait = 'seed-wait';
    case Seeding = 'seeding';

    public static function fromRpc(int $status): self
    {
        return match ($status) {
            1 => self::CheckWait,
            2 => self::Checking,
            3 => self::DownloadWait,
            4 => self::Downloading,
            5 => self::SeedWait,
            6 => self::Seeding,
            default => self::Stopped,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Stopped => 'Зупинено',
            self::CheckWait => 'Черга перевірки',
            self::Checking => 'Перевірка',
            self::DownloadWait => 'Черга завантаження',
            self::Downloading => 'Завантаження',
            self::SeedWait => 'Черга роздачі',
            self::Seeding => 'Роздача',
        };
    }

    /**
     * Whether the torrent is stopped, and therefore startable.
     */
    public function isPaused(): bool
    {
        return $this === self::Stopped;
    }
}

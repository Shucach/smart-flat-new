<?php

namespace App\Modules\Torrent\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * What the daemon is doing right now, plus the room left on the download disk.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class SessionStats implements Arrayable
{
    public function __construct(
        public int $rateDownload,
        public int $rateUpload,
        public int $torrentCount,
        public int $activeTorrentCount,
        public int $pausedTorrentCount,
        public int $sessionDownloadedBytes,
        public int $sessionUploadedBytes,
        public int $totalDownloadedBytes,
        public int $totalUploadedBytes,
        public int $freeSpaceBytes,
        public int $totalSpaceBytes,
        public string $version,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rateDownload' => $this->rateDownload,
            'rateUpload' => $this->rateUpload,
            'torrentCount' => $this->torrentCount,
            'activeTorrentCount' => $this->activeTorrentCount,
            'pausedTorrentCount' => $this->pausedTorrentCount,
            'sessionDownloadedBytes' => $this->sessionDownloadedBytes,
            'sessionUploadedBytes' => $this->sessionUploadedBytes,
            'totalDownloadedBytes' => $this->totalDownloadedBytes,
            'totalUploadedBytes' => $this->totalUploadedBytes,
            'freeSpaceBytes' => $this->freeSpaceBytes,
            'totalSpaceBytes' => $this->totalSpaceBytes,
            'version' => $this->version,
        ];
    }
}

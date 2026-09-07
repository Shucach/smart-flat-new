<?php

namespace App\Modules\Torrent\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class TorrentTracker implements Arrayable
{
    public function __construct(
        public int $id,
        public string $host,
        public string $announce,
        public int $seederCount,
        public int $leecherCount,
        public ?string $lastAnnounceResult,
        public bool $lastAnnounceSucceeded,
    ) {}

    /**
     * @return array{id: int, host: string, announce: string, seederCount: int, leecherCount: int, lastAnnounceResult: string|null, lastAnnounceSucceeded: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'host' => $this->host,
            'announce' => $this->announce,
            'seederCount' => $this->seederCount,
            'leecherCount' => $this->leecherCount,
            'lastAnnounceResult' => $this->lastAnnounceResult,
            'lastAnnounceSucceeded' => $this->lastAnnounceSucceeded,
        ];
    }
}

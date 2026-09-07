<?php

namespace App\Modules\Torrent\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class TorrentOverview implements Arrayable
{
    /**
     * @param  array<int, Torrent>  $torrents
     */
    public function __construct(
        public array $torrents,
        public SessionStats $stats,
        public SessionSettings $settings,
    ) {}

    /**
     * @return array{torrents: array<int, array<string, mixed>>, stats: array<string, mixed>, settings: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'torrents' => array_map(static fn (Torrent $torrent): array => $torrent->toArray(), $this->torrents),
            'stats' => $this->stats->toArray(),
            'settings' => $this->settings->toArray(),
        ];
    }
}

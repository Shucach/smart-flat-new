<?php

namespace App\Modules\Torrent\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * Everything the detail panel shows for a single torrent: its files, where the
 * pieces are coming from, and the limits that apply to it alone.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class TorrentDetail implements Arrayable
{
    /**
     * @param  array<int, TorrentFile>  $files
     * @param  array<int, TorrentTracker>  $trackers
     * @param  array<int, TorrentPeer>  $peers
     */
    public function __construct(
        public Torrent $torrent,
        public array $files,
        public array $trackers,
        public array $peers,
        public TorrentLimits $limits,
        public ?string $comment,
        public ?string $creator,
        public ?string $magnetLink,
        public ?Carbon $lastActivityAt,
        public int $pieceCount,
        public int $pieceSizeBytes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->torrent->toArray(),
            'files' => array_map(static fn (TorrentFile $file): array => $file->toArray(), $this->files),
            'trackers' => array_map(static fn (TorrentTracker $tracker): array => $tracker->toArray(), $this->trackers),
            'peers' => array_map(static fn (TorrentPeer $peer): array => $peer->toArray(), $this->peers),
            'limits' => $this->limits->toArray(),
            'comment' => $this->comment,
            'creator' => $this->creator,
            'magnetLink' => $this->magnetLink,
            'lastActivityAt' => $this->lastActivityAt?->toIso8601String(),
            'pieceCount' => $this->pieceCount,
            'pieceSizeBytes' => $this->pieceSizeBytes,
        ];
    }
}

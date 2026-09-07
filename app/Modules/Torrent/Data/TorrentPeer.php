<?php

namespace App\Modules\Torrent\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class TorrentPeer implements Arrayable
{
    public function __construct(
        public string $address,
        public string $client,
        public float $progress,
        public int $rateToClient,
        public int $rateToPeer,
        public string $flags,
    ) {}

    /**
     * @return array{address: string, client: string, progressPercent: float, rateDownload: int, rateUpload: int, flags: string}
     */
    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'client' => $this->client,
            'progressPercent' => round($this->progress * 100, 1),
            'rateDownload' => $this->rateToClient,
            'rateUpload' => $this->rateToPeer,
            'flags' => $this->flags,
        ];
    }
}

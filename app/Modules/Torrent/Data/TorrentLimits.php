<?php

namespace App\Modules\Torrent\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * The per-torrent overrides of the session limits.
 *
 * A null speed means "no own limit": the torrent follows the session settings
 * it honours. `seedRatio` is null for the same reason -- Transmission's
 * `seedRatioMode` 0 defers to the global ratio, 2 seeds forever.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class TorrentLimits implements Arrayable
{
    public function __construct(
        public ?int $downloadKilobytes,
        public ?int $uploadKilobytes,
        public ?float $seedRatio,
        public bool $seedForever = false,
        public bool $honorsSessionLimits = true,
    ) {}

    /**
     * @return array{downloadKilobytes: int|null, uploadKilobytes: int|null, seedRatio: float|null, seedForever: bool, honorsSessionLimits: bool}
     */
    public function toArray(): array
    {
        return [
            'downloadKilobytes' => $this->downloadKilobytes,
            'uploadKilobytes' => $this->uploadKilobytes,
            'seedRatio' => $this->seedRatio,
            'seedForever' => $this->seedForever,
            'honorsSessionLimits' => $this->honorsSessionLimits,
        ];
    }
}

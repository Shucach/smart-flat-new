<?php

namespace App\Modules\Torrent\Data;

use App\Enums\TorrentFilePriority;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Number;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class TorrentFile implements Arrayable
{
    public function __construct(
        public int $index,
        public string $name,
        public int $lengthBytes,
        public int $completedBytes,
        public bool $wanted,
        public TorrentFilePriority $priority,
    ) {}

    public function percentDone(): float
    {
        if ($this->lengthBytes <= 0) {
            return 100.0;
        }

        return round($this->completedBytes / $this->lengthBytes * 100, 1);
    }

    /**
     * @return array{index: int, name: string, lengthBytes: int, completedBytes: int, sizeForHumans: string, percentDone: float, wanted: bool, priority: string}
     */
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'name' => $this->name,
            'lengthBytes' => $this->lengthBytes,
            'completedBytes' => $this->completedBytes,
            'sizeForHumans' => Number::fileSize($this->lengthBytes, maxPrecision: 1),
            'percentDone' => $this->percentDone(),
            'wanted' => $this->wanted,
            'priority' => $this->priority->value,
        ];
    }
}

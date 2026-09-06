<?php

namespace App\Modules\Media\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class MediaEntry implements Arrayable
{
    public function __construct(
        public string $name,
        public string $path,
        public bool $isDirectory,
        public ?int $size,
        public ?Carbon $modifiedAt,
    ) {}

    /**
     * @return array{name: string, path: string, isDirectory: bool, size: int|null, sizeForHumans: string|null, modifiedAt: string|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'isDirectory' => $this->isDirectory,
            'size' => $this->size,
            'sizeForHumans' => $this->size === null ? null : Number::fileSize($this->size, maxPrecision: 1),
            'modifiedAt' => $this->modifiedAt?->toIso8601String(),
        ];
    }
}

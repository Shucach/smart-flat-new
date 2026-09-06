<?php

namespace App\Modules\Frame\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class FramePage implements Arrayable
{
    /**
     * @param  array<int, FrameImage>  $images
     */
    public function __construct(
        public array $images,
        public int $page,
        public int $perPage,
        public int $lastPage,
        public int $total,
    ) {}

    /**
     * @return array{images: array<int, array{name: string, preview: string, kind: string}>, pagination: array{page: int, perPage: int, lastPage: int, total: int}}
     */
    public function toArray(): array
    {
        return [
            'images' => array_map(static fn (FrameImage $image): array => $image->toArray(), $this->images),
            'pagination' => [
                'page' => $this->page,
                'perPage' => $this->perPage,
                'lastPage' => $this->lastPage,
                'total' => $this->total,
            ],
        ];
    }
}

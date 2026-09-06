<?php

namespace App\Modules\Frame\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class FrameImage implements Arrayable
{
    public function __construct(
        public string $name,
        public string $preview,
    ) {}

    /**
     * @return array{name: string, preview: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'preview' => $this->preview,
        ];
    }
}

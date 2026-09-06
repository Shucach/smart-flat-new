<?php

namespace App\Modules\Frame\Data;

use App\Enums\FrameUploadKind;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class FrameImage implements Arrayable
{
    public function __construct(
        public string $name,
        public string $preview,
        public FrameUploadKind $kind = FrameUploadKind::Image,
    ) {}

    /**
     * A clip previews as a still lifted out of it, so the gallery needs the kind
     * to tell the two apart and mark the clip as playable.
     *
     * @return array{name: string, preview: string, kind: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'preview' => $this->preview,
            'kind' => $this->kind->value,
        ];
    }
}

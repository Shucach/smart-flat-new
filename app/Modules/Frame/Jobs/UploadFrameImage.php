<?php

namespace App\Modules\Frame\Jobs;

use App\Enums\FrameUploadStatus;
use App\Modules\Frame\Contracts\FrameGateway;

/**
 * Sends one photograph to the frame, which resizes and crops it on arrival.
 */
class UploadFrameImage extends ProcessFrameUpload
{
    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function handle(FrameGateway $frame): void
    {
        if (! $this->hasSource()) {
            return;
        }

        $this->upload->markStatus(FrameUploadStatus::Uploading, 50);

        $this->send(
            $frame,
            $this->disk()->path((string) $this->upload->stored_path),
            $this->upload->original_name,
        );
    }
}

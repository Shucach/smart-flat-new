<?php

namespace App\Modules\Frame\Jobs;

use App\Modules\Frame\Contracts\FrameGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UploadFrameImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    /**
     * @param  string  $storedPath  Path of the temporary upload on the local disk.
     */
    public function __construct(
        public string $storedPath,
        public string $originalName,
    ) {}

    public function handle(FrameGateway $frame): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($this->storedPath)) {
            return;
        }

        $frame->upload($disk->path($this->storedPath), $this->originalName);

        $disk->delete($this->storedPath);
    }

    public function failed(?Throwable $exception): void
    {
        Storage::disk('local')->delete($this->storedPath);

        Log::error('Не вдалося завантажити зображення в розумну рамку.', [
            'stored_path' => $this->storedPath,
            'original_name' => $this->originalName,
            'exception' => $exception,
        ]);
    }
}

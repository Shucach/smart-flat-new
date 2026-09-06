<?php

namespace App\Modules\Frame\Jobs;

use App\Enums\FrameUploadStatus;
use App\Models\FrameUpload;
use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Exceptions\FrameException;
use App\Modules\Frame\Exceptions\TranscodeException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * What every file dropped on the frame page has in common: it waits on the
 * queue, it reports where it has got to, and it leaves nothing behind on disk.
 *
 * Photographs go straight out; clips are converted first. Both are slow enough
 * to be worth watching from the browser, which is what the `FrameUpload` row is
 * for.
 */
abstract class ProcessFrameUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(public FrameUpload $upload) {}

    public function failed(?Throwable $exception): void
    {
        $this->closeAsFailed($this->reason($exception), $exception);
    }

    /**
     * Hands the file to the frame and closes the row out with the name it was
     * stored under.
     */
    protected function send(FrameGateway $frame, string $absolutePath, string $originalName): void
    {
        $this->upload->markStatus(FrameUploadStatus::Uploading, $this->upload->progress);

        try {
            $storedName = $frame->upload($absolutePath, $originalName);
        } catch (FrameException $exception) {
            // A frame that has read the file and refused it will refuse it again,
            // so the row is closed here and the job ends rather than being retried
            // twice more to reach the same answer.
            if ($exception->permanent) {
                $this->closeAsFailed($exception->getMessage(), $exception);

                return;
            }

            throw $exception;
        }

        $this->discardWorkingFiles();

        $this->upload->markCompleted($storedName);
    }

    /**
     * Reports whether the file this job was given is still on disk, closing the
     * row out when it is not.
     */
    protected function hasSource(): bool
    {
        if ($this->upload->stored_path !== null && $this->disk()->exists($this->upload->stored_path)) {
            return true;
        }

        $this->closeAsFailed('Тимчасовий файл зник до обробки.', null);

        return false;
    }

    protected function disk(): Filesystem
    {
        return Storage::disk('local');
    }

    /**
     * Removes whatever this job wrote to the local disk.
     */
    protected function discardWorkingFiles(): void
    {
        if ($this->upload->stored_path !== null) {
            $this->disk()->delete($this->upload->stored_path);
        }
    }

    /**
     * Records why this upload will not reach the frame, and clears its files.
     */
    private function closeAsFailed(string $reason, ?Throwable $exception): void
    {
        $this->discardWorkingFiles();

        $this->upload->markFailed($reason);

        Log::error('Не вдалося завантажити файл у розумну рамку.', [
            'upload_id' => $this->upload->id,
            'original_name' => $this->upload->original_name,
            'reason' => $reason,
            'exception' => $exception,
        ]);
    }

    private function reason(?Throwable $exception): string
    {
        if ($exception instanceof FrameException || $exception instanceof TranscodeException) {
            return $exception->getMessage();
        }

        return 'Не вдалося обробити файл. Спробуйте ще раз.';
    }
}

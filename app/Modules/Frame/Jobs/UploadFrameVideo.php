<?php

namespace App\Modules\Frame\Jobs;

use App\Enums\FrameUploadStatus;
use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Contracts\VideoTranscoder;

/**
 * Converts one clip into the only thing the frame can play, then sends it.
 *
 * Transcoding is by far the slowest thing this application does -- minutes of a
 * Raspberry Pi's four cores for a minute of video -- which is the whole reason
 * the frame page reads progress out of the database instead of holding a request
 * open.
 */
class UploadFrameVideo extends ProcessFrameUpload
{
    /**
     * The encode is kept on disk between attempts, so a second try costs only
     * the upload; a third would mean the frame has been down for a while.
     */
    public int $tries = 2;

    /** @var array<int, int> */
    public array $backoff = [30];

    /**
     * Percentage the row sits at once the clip is encoded and only the transfer
     * to the frame is left.
     */
    private const int ENCODED = 90;

    public function handle(FrameGateway $frame, VideoTranscoder $transcoder): void
    {
        if (! $this->hasSource()) {
            return;
        }

        $encodedPath = $this->encodedPath();
        $disk = $this->disk();

        if (! $disk->exists($encodedPath)) {
            $this->upload->markStatus(FrameUploadStatus::Transcoding, 0);

            $transcoder->transcode(
                $disk->path((string) $this->upload->stored_path),
                $this->prepareTarget($encodedPath),
                fn (int $percent) => $this->reportEncoding($percent),
            );
        }

        $this->upload->markStatus(FrameUploadStatus::Transcoding, self::ENCODED);

        $this->send($frame, $disk->path($encodedPath), $this->frameName());
    }

    /**
     * A worker must outlive the encode it is running, so the ceiling here is the
     * transcoder's own, with room for the upload that follows it.
     */
    public function timeout(): int
    {
        return (int) config('smartflat.frame.video.timeout', 1800) + 300;
    }

    protected function discardWorkingFiles(): void
    {
        parent::discardWorkingFiles();

        $this->disk()->delete($this->encodedPath());
    }

    /**
     * Keeps the encode out of the source's way and off the frame's name space.
     */
    private function encodedPath(): string
    {
        return 'frame-uploads/encoded/'.$this->upload->id.'.mp4';
    }

    private function prepareTarget(string $path): string
    {
        $disk = $this->disk();
        $disk->makeDirectory(dirname($path));

        $absolute = $disk->path($path);

        // ffmpeg refuses to overwrite half of a file left by a killed attempt.
        if ($disk->exists($path)) {
            $disk->delete($path);
        }

        return $absolute;
    }

    /**
     * The frame stores a clip under the name it arrives with, and only accepts
     * MP4 extensions, so a `.mov` from a phone is renamed on the way out.
     */
    private function frameName(): string
    {
        $stem = trim(pathinfo($this->upload->original_name, PATHINFO_FILENAME));

        return ($stem === '' ? 'video' : $stem).'.mp4';
    }

    /**
     * Maps ffmpeg's own progress onto the part of the bar the encode owns.
     */
    private function reportEncoding(int $percent): void
    {
        $progress = (int) round($percent / 100 * self::ENCODED);

        // ffmpeg reports several times a second; only a change is worth a write.
        if ($progress === $this->upload->progress) {
            return;
        }

        $this->upload->markStatus(FrameUploadStatus::Transcoding, $progress);
    }
}

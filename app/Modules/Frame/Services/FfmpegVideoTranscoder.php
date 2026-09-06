<?php

namespace App\Modules\Frame\Services;

use App\Modules\Frame\Contracts\VideoTranscoder;
use App\Modules\Frame\Exceptions\TranscodeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Converts whatever the phone recorded into what the panel can decode.
 *
 * The frame runs a BCM2835 with no HEVC block at all, so an iPhone clip — HEVC by
 * default — can never be played there, and the frame refuses anything that is not
 * H.264 at the panel's own 600x1024 shape. Neither the conversion nor the crop can
 * happen on the frame itself: that board takes minutes over a few seconds of video.
 * So it happens here, and because even here it is slow, it happens on the queue and
 * reports how far along it is.
 */
final readonly class FfmpegVideoTranscoder implements VideoTranscoder
{
    private const int PROBE_TIMEOUT = 60;

    /**
     * What a shell reports when the binary it was asked to run does not exist.
     */
    private const int COMMAND_NOT_FOUND = 127;

    /**
     * PHP-FPM inherits a minimal PATH, which is rarely where ffmpeg lives.
     */
    private const string SEARCH_PATH = '/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin';

    public function __construct(
        private string $ffmpeg,
        private string $ffprobe,
        private int $width,
        private int $height,
        private int $frameRate,
        private string $bitrate,
        private int $maxSeconds,
        private int $timeout,
    ) {}

    public function transcode(string $sourcePath, string $targetPath, callable $onProgress): void
    {
        $duration = $this->duration($sourcePath);

        if ($duration > $this->maxSeconds) {
            throw TranscodeException::tooLong($duration, $this->maxSeconds);
        }

        $onProgress(0);

        $process = $this->process($this->encodeCommand($sourcePath, $targetPath), $this->timeout);

        try {
            $process->run(function (string $type, string $buffer) use ($duration, $onProgress): void {
                if ($type === Process::OUT) {
                    $this->reportProgress($buffer, $duration, $onProgress);
                }
            });
        } catch (ProcessTimedOutException) {
            throw TranscodeException::failed('перекодування тривало надто довго');
        }

        if (! $process->isSuccessful()) {
            if ($process->getExitCode() === self::COMMAND_NOT_FOUND) {
                throw TranscodeException::toolingMissing($this->ffmpeg);
            }

            throw TranscodeException::failed($this->lastLine($process->getErrorOutput()));
        }

        if (! is_file($targetPath) || filesize($targetPath) === 0) {
            throw TranscodeException::failed('ffmpeg не записав файл');
        }

        $onProgress(100);
    }

    /**
     * Returns the clip length in seconds, or raises when the file is not a video.
     */
    private function duration(string $sourcePath): float
    {
        $process = $this->process([
            $this->ffprobe, '-v', 'error',
            '-print_format', 'json',
            '-show_format', '-show_streams',
            $sourcePath,
        ], self::PROBE_TIMEOUT);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw TranscodeException::failed('ffprobe не встиг прочитати файл');
        }

        if ($process->getExitCode() === self::COMMAND_NOT_FOUND) {
            throw TranscodeException::toolingMissing($this->ffprobe);
        }

        if (! $process->isSuccessful()) {
            throw TranscodeException::unreadable();
        }

        $payload = json_decode($process->getOutput(), true);

        if (! is_array($payload)) {
            throw TranscodeException::unreadable();
        }

        $track = $this->videoTrack($payload);

        // Sound files and containers of nothing but subtitles get this far, and
        // there is nothing for the panel to show in either.
        if ($track === null) {
            throw TranscodeException::unreadable();
        }

        $format = is_array($payload['format'] ?? null) ? $payload['format'] : [];

        // A stream copied out of a phone sometimes carries no duration of its own,
        // in which case the container's is the only one there is.
        return (float) ($format['duration'] ?? $track['duration'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function videoTrack(array $payload): ?array
    {
        foreach (is_array($payload['streams'] ?? null) ? $payload['streams'] : [] as $stream) {
            if (is_array($stream) && ($stream['codec_type'] ?? null) === 'video') {
                return $stream;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function encodeCommand(string $sourcePath, string $targetPath): array
    {
        return [
            $this->ffmpeg, '-nostdin', '-hide_banner', '-v', 'error', '-y',
            '-i', $sourcePath,
            // The same centre crop the frame applies to a photograph, so a clip and
            // a still of the same scene are framed alike in the gallery.
            '-vf', sprintf(
                'scale=%d:%d:force_original_aspect_ratio=increase,crop=%d:%d',
                $this->width, $this->height, $this->width, $this->height,
            ),
            '-c:v', 'libx264', '-preset', 'veryfast',
            '-profile:v', 'high', '-level', '4.0', '-pix_fmt', 'yuv420p',
            '-r', (string) $this->frameRate,
            '-b:v', $this->bitrate, '-maxrate', $this->bitrate, '-bufsize', $this->bitrate,
            // The frame plays without sound, and `+faststart` puts the index at the
            // head of the file so the player starts without reading all of it.
            '-an', '-movflags', '+faststart',
            '-progress', 'pipe:1', '-nostats',
            $targetPath,
        ];
    }

    /**
     * Turns an `-progress` block into a percentage.
     *
     * ffmpeg writes `key=value` lines to the pipe a few times a second; the one
     * that matters is the position it has reached in the source.
     *
     * @param  callable(int): void  $onProgress
     */
    private function reportProgress(string $buffer, float $duration, callable $onProgress): void
    {
        if ($duration <= 0 || preg_match_all('/out_time_us=(\d+)/', $buffer, $matches) === 0) {
            return;
        }

        $microseconds = (float) end($matches[1]);

        // The last percent is kept for the file actually being on disk.
        $onProgress((int) min(99, floor($microseconds / ($duration * 1_000_000) * 100)));
    }

    /**
     * @param  array<int, string>  $command
     */
    private function process(array $command, int $timeout): Process
    {
        $process = new Process($command, env: ['PATH' => self::SEARCH_PATH]);
        $process->setTimeout($timeout);

        return $process;
    }

    private function lastLine(string $output): string
    {
        $lines = array_values(array_filter(
            array_map(trim(...), explode("\n", $output)),
            static fn (string $line): bool => $line !== '',
        ));

        return $lines === [] ? 'невідома помилка ffmpeg' : $lines[count($lines) - 1];
    }
}

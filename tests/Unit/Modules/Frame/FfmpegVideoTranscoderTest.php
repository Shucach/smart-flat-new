<?php

use App\Modules\Frame\Exceptions\TranscodeException;
use App\Modules\Frame\Services\FfmpegVideoTranscoder;

/**
 * ffmpeg is stubbed with shell scripts: what is under test is how its output is
 * read, not the encoder itself, and the real one is not installed everywhere.
 */
beforeEach(function () {
    $this->workspace = sys_get_temp_dir().'/frame-transcoder-'.uniqid();
    mkdir($this->workspace);

    $this->source = $this->workspace.'/source.mov';
    file_put_contents($this->source, 'clip-bytes');

    $this->target = $this->workspace.'/encoded.mp4';

    $this->stub = function (string $name, string $body): string {
        $path = $this->workspace.'/'.$name;
        file_put_contents($path, "#!/bin/sh\n".$body."\n");
        chmod($path, 0o755);

        return $path;
    };

    $this->transcoder = function (string $ffmpeg, string $ffprobe): FfmpegVideoTranscoder {
        return new FfmpegVideoTranscoder($ffmpeg, $ffprobe, 600, 1024, 25, '2M', 60, 30);
    };

    $this->probing = fn (float $duration): string => ($this->stub)('ffprobe', sprintf(
        'echo \'{"streams":[{"codec_type":"video","width":1080,"height":1920}],"format":{"duration":"%s"}}\'',
        $duration,
    ));
});

afterEach(function () {
    array_map(unlink(...), glob($this->workspace.'/*') ?: []);
    rmdir($this->workspace);
});

it('turns the ffmpeg progress stream into percentages', function () {
    $ffmpeg = ($this->stub)('ffmpeg', <<<'SH'
    for us in 2000000 3000000 5000000
    do
        echo "frame=100"
        echo "out_time_us=$us"
        echo "progress=continue"
    done
    eval "target=\${$#}"
    echo encoded > "$target"
    SH);

    $reported = [];

    ($this->transcoder)($ffmpeg, ($this->probing)(10.0))
        ->transcode($this->source, $this->target, function (int $percent) use (&$reported) {
            $reported[] = $percent;
        });

    // How ffmpeg's writes land in the pipe is up to the operating system, so the
    // run is judged by its shape: it opens at nothing, climbs with the clip, and
    // closes at a hundred only once the file is written.
    $sorted = $reported;
    sort($sorted);

    expect($reported)->toBe($sorted)
        ->and($reported[0])->toBe(0)
        ->and(end($reported))->toBe(100)
        // The stub stops five seconds into a ten second clip.
        ->and($reported[count($reported) - 2])->toBe(50)
        ->and(file_get_contents($this->target))->toBe("encoded\n");
});

it('refuses a clip longer than the frame page allows', function () {
    $ffmpeg = ($this->stub)('ffmpeg', 'exit 0');

    expect(fn () => ($this->transcoder)($ffmpeg, ($this->probing)(94.2))
        ->transcode($this->source, $this->target, fn () => null))
        ->toThrow(TranscodeException::class, 'Ролик триває 95 с, а максимум — 60 с.');
});

it('refuses a file that carries no video track', function () {
    $ffprobe = ($this->stub)('ffprobe', 'echo \'{"streams":[{"codec_type":"audio"}],"format":{"duration":"3.0"}}\'');

    expect(fn () => ($this->transcoder)(($this->stub)('ffmpeg', 'exit 0'), $ffprobe)
        ->transcode($this->source, $this->target, fn () => null))
        ->toThrow(TranscodeException::class, 'Не вдалося прочитати відеофайл.');
});

it('repeats the last thing ffmpeg said when the encode fails', function () {
    $ffmpeg = ($this->stub)('ffmpeg', "echo 'Invalid data found when processing input' >&2\nexit 1");

    expect(fn () => ($this->transcoder)($ffmpeg, ($this->probing)(5.0))
        ->transcode($this->source, $this->target, fn () => null))
        ->toThrow(TranscodeException::class, 'Invalid data found when processing input');
});

it('says which tool is missing when it is not installed', function () {
    expect(fn () => ($this->transcoder)('ffmpeg-that-is-not-here', 'ffprobe-that-is-not-here')
        ->transcode($this->source, $this->target, fn () => null))
        ->toThrow(TranscodeException::class, 'не знайдено ffprobe-that-is-not-here');
});

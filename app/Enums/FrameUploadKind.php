<?php

namespace App\Enums;

use Illuminate\Http\UploadedFile;

enum FrameUploadKind: string
{
    case Image = 'image';
    case Video = 'video';

    /**
     * The container extensions the frame will play, which are also the ones
     * ffmpeg is asked to write.
     */
    public const array VIDEO_EXTENSIONS = ['mp4', 'm4v', 'mov'];

    /**
     * Reads the kind off an upload, trusting the browser's mime type first and
     * the file name only when that says nothing useful.
     */
    public static function forUpload(UploadedFile $file): self
    {
        if (str_starts_with((string) $file->getMimeType(), 'video/')) {
            return self::Video;
        }

        return in_array(strtolower($file->getClientOriginalExtension()), self::VIDEO_EXTENSIONS, true)
            ? self::Video
            : self::Image;
    }

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Зображення',
            self::Video => 'Відео',
        };
    }
}

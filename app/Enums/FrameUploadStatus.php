<?php

namespace App\Enums;

enum FrameUploadStatus: string
{
    case Queued = 'queued';
    case Transcoding = 'transcoding';
    case Uploading = 'uploading';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'У черзі',
            self::Transcoding => 'Перекодування відео',
            self::Uploading => 'Надсилання в рамку',
            self::Completed => 'Готово',
            self::Failed => 'Помилка',
        };
    }

    /**
     * Reports whether the pipeline has stopped touching this upload.
     */
    public function isFinished(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}

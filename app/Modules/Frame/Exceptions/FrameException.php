<?php

namespace App\Modules\Frame\Exceptions;

use RuntimeException;

class FrameException extends RuntimeException
{
    /**
     * Whether retrying could ever change the outcome. A frame that is merely
     * unreachable is worth another attempt; one that has read the file and said
     * no is not.
     */
    public bool $permanent = false;

    public static function unavailable(string $reason): self
    {
        return new self("Розумна рамка недоступна: {$reason}");
    }

    /**
     * The frame inspected the file and refused it, saying why: the wrong codec,
     * the wrong shape, a clip that is too long. Worth repeating verbatim.
     */
    public static function rejected(string $reason): self
    {
        $exception = new self("Рамка не прийняла файл: {$reason}");
        $exception->permanent = true;

        return $exception;
    }

    public static function notConfigured(): self
    {
        return new self('Розумну рамку не налаштовано.');
    }
}

<?php

namespace App\Modules\Frame\Exceptions;

use RuntimeException;

class FrameException extends RuntimeException
{
    public static function unavailable(string $reason): self
    {
        return new self("Розумна рамка недоступна: {$reason}");
    }

    public static function notConfigured(): self
    {
        return new self('Розумну рамку не налаштовано.');
    }
}

<?php

namespace App\Modules\Frame\Exceptions;

use RuntimeException;

class TranscodeException extends RuntimeException
{
    public static function unreadable(): self
    {
        return new self('Не вдалося прочитати відеофайл.');
    }

    public static function tooLong(float $seconds, int $limit): self
    {
        return new self(sprintf('Ролик триває %d с, а максимум — %d с.', (int) ceil($seconds), $limit));
    }

    public static function toolingMissing(string $binary): self
    {
        return new self("Перекодування недоступне: не знайдено {$binary}.");
    }

    public static function failed(string $reason): self
    {
        return new self("Не вдалося перекодувати відео: {$reason}");
    }
}

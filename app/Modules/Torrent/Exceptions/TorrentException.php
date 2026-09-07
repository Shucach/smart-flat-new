<?php

namespace App\Modules\Torrent\Exceptions;

use RuntimeException;

class TorrentException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Торент-клієнт не налаштовано.');
    }

    public static function unavailable(string $reason): self
    {
        return new self("Торент-клієнт недоступний: {$reason}");
    }

    /**
     * The daemon answered, and the answer was "no" -- a duplicate torrent, a
     * magnet it cannot parse, a directory it cannot write to. Worth repeating.
     */
    public static function rejected(string $reason): self
    {
        return new self("Торент-клієнт відхилив запит: {$reason}");
    }

    public static function missing(int $id): self
    {
        return new self("Торент [{$id}] не знайдено.");
    }
}

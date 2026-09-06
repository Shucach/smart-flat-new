<?php

namespace App\Modules\Frame\Services;

use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Data\FrameImage;
use App\Modules\Frame\Data\FramePage;

/**
 * A deterministic in-memory frame used for local development and tests.
 */
final class NullFrameGateway implements FrameGateway
{
    private const string PREVIEW = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==';

    /** @var array<int, string> */
    private array $names;

    private int $restarts = 0;

    /**
     * @param  array<int, string>|null  $names
     */
    public function __construct(?array $names = null)
    {
        $this->names = $names ?? array_map(
            static fn (int $index): string => sprintf('demo-%02d.jpg', $index),
            range(1, 7),
        );
    }

    public function list(int $page, int $perPage): FramePage
    {
        $total = count($this->names);
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $lastPage);

        $images = array_map(
            static fn (string $name): FrameImage => new FrameImage($name, self::PREVIEW),
            array_slice($this->names, ($page - 1) * $perPage, $perPage),
        );

        return new FramePage(
            images: $images,
            page: $page,
            perPage: $perPage,
            lastPage: $lastPage,
            total: $total,
        );
    }

    public function upload(string $absolutePath, string $originalName): void
    {
        $this->names[] = $originalName;
    }

    public function delete(array $names): void
    {
        $this->names = array_values(array_diff($this->names, $names));
    }

    public function restartSlideshow(): void
    {
        $this->restarts++;
    }

    public function restarts(): int
    {
        return $this->restarts;
    }
}

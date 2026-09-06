<?php

namespace App\Modules\Frame\Contracts;

use App\Modules\Frame\Data\FramePage;
use App\Modules\Frame\Exceptions\FrameException;

interface FrameGateway
{
    /**
     * @throws FrameException
     */
    public function list(int $page, int $perPage): FramePage;

    /**
     * Sends one file to the frame and returns the name it was stored under.
     *
     * @throws FrameException
     */
    public function upload(string $absolutePath, string $originalName): string;

    /**
     * @param  array<int, string>  $names
     *
     * @throws FrameException
     */
    public function delete(array $names): void;

    /**
     * Reloads the picture list the panel is showing.
     *
     * @throws FrameException
     */
    public function restartSlideshow(): void;
}

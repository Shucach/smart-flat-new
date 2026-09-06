<?php

namespace App\Modules\Media\Contracts;

use App\Modules\Media\Data\MediaListing;
use App\Modules\Media\Exceptions\MediaPathException;

interface MediaLibrary
{
    /**
     * List the contents of a directory relative to the media root.
     *
     * @throws MediaPathException
     */
    public function list(string $path): MediaListing;

    /**
     * Delete a file or a directory relative to the media root.
     *
     * @throws MediaPathException
     */
    public function delete(string $path): void;
}

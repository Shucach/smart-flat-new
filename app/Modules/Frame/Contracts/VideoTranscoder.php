<?php

namespace App\Modules\Frame\Contracts;

use App\Modules\Frame\Exceptions\TranscodeException;

interface VideoTranscoder
{
    /**
     * Rewrites a clip into the one shape the frame can play, reporting progress.
     *
     * @param  callable(int): void  $onProgress  Called with the percentage done, 0-100.
     *
     * @throws TranscodeException
     */
    public function transcode(string $sourcePath, string $targetPath, callable $onProgress): void;
}

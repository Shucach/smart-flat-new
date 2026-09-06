<?php

namespace App\Modules\Frame\Services;

use App\Modules\Frame\Contracts\VideoTranscoder;

/**
 * Copies the clip through untouched, for local development and tests where
 * ffmpeg is neither present nor the thing under test.
 */
final class NullVideoTranscoder implements VideoTranscoder
{
    /** @var array<int, array{source: string, target: string}> */
    private array $calls = [];

    public function transcode(string $sourcePath, string $targetPath, callable $onProgress): void
    {
        $this->calls[] = ['source' => $sourcePath, 'target' => $targetPath];

        $onProgress(0);
        $onProgress(50);

        copy($sourcePath, $targetPath);

        $onProgress(100);
    }

    /**
     * @return array<int, array{source: string, target: string}>
     */
    public function calls(): array
    {
        return $this->calls;
    }
}

<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Contracts\MediaLibrary;
use App\Modules\Media\Data\MediaEntry;
use App\Modules\Media\Data\MediaListing;
use App\Modules\Media\Exceptions\MediaPathException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;

final readonly class LocalMediaLibrary implements MediaLibrary
{
    /**
     * @param  array<int, string>  $ignoredExtensions
     */
    public function __construct(
        private Filesystem $files,
        private string $root,
        private array $ignoredExtensions = [],
    ) {}

    public function list(string $path): MediaListing
    {
        $absolutePath = $this->resolve($path);

        if (! $this->files->isDirectory($absolutePath)) {
            throw MediaPathException::missing($path);
        }

        $relativePath = $this->relativize($absolutePath);

        return new MediaListing(
            path: $relativePath,
            parentPath: $this->parentPathOf($relativePath),
            entries: $this->entriesIn($absolutePath, $relativePath),
        );
    }

    public function delete(string $path): void
    {
        $absolutePath = $this->resolve($path);

        if ($absolutePath === $this->rootPath()) {
            throw MediaPathException::rootIsProtected();
        }

        if ($this->files->isDirectory($absolutePath)) {
            $this->files->deleteDirectory($absolutePath);

            return;
        }

        $this->files->delete($absolutePath);
    }

    /**
     * Resolve a user supplied path into an absolute path inside the media root.
     *
     * The resolved real path is compared against the real path of the root so
     * that traversal sequences and symlinks cannot escape the media library.
     *
     * @throws MediaPathException
     */
    public function resolve(string $path): string
    {
        if (str_contains($path, "\0")) {
            throw MediaPathException::outsideRoot($path);
        }

        $root = $this->rootPath();
        $candidate = realpath($root.DIRECTORY_SEPARATOR.trim(str_replace('\\', '/', $path), '/'));

        if ($candidate === false) {
            throw MediaPathException::missing($path);
        }

        if ($candidate !== $root && ! str_starts_with($candidate, $root.DIRECTORY_SEPARATOR)) {
            throw MediaPathException::outsideRoot($path);
        }

        return $candidate;
    }

    /**
     * @return array<int, MediaEntry>
     */
    private function entriesIn(string $absolutePath, string $relativePath): array
    {
        $entries = [];

        foreach (scandir($absolutePath) ?: [] as $name) {
            if (str_starts_with($name, '.') || $this->isIgnored($name)) {
                continue;
            }

            $child = $absolutePath.DIRECTORY_SEPARATOR.$name;
            $isDirectory = is_dir($child);
            $modifiedAt = filemtime($child);

            $entries[] = new MediaEntry(
                name: $name,
                path: $relativePath === '' ? $name : $relativePath.'/'.$name,
                isDirectory: $isDirectory,
                size: $isDirectory ? null : (filesize($child) ?: 0),
                modifiedAt: $modifiedAt === false ? null : Carbon::createFromTimestamp($modifiedAt),
            );
        }

        usort($entries, static function (MediaEntry $first, MediaEntry $second): int {
            return [! $first->isDirectory, mb_strtolower($first->name)]
                <=> [! $second->isDirectory, mb_strtolower($second->name)];
        });

        return $entries;
    }

    /**
     * Extensions listed in the configuration never reach the browser.
     */
    private function isIgnored(string $name): bool
    {
        $extension = mb_strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return in_array($extension, array_map(mb_strtolower(...), $this->ignoredExtensions), true);
    }

    private function relativize(string $absolutePath): string
    {
        return trim(str_replace($this->rootPath(), '', $absolutePath), DIRECTORY_SEPARATOR);
    }

    private function parentPathOf(string $relativePath): ?string
    {
        if ($relativePath === '') {
            return null;
        }

        $segments = explode('/', $relativePath);
        array_pop($segments);

        return implode('/', $segments);
    }

    private function rootPath(): string
    {
        $root = realpath($this->root);

        if ($root === false) {
            throw MediaPathException::missing($this->root);
        }

        return rtrim($root, DIRECTORY_SEPARATOR);
    }
}

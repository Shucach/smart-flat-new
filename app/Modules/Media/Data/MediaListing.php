<?php

namespace App\Modules\Media\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class MediaListing implements Arrayable
{
    /**
     * @param  array<int, MediaEntry>  $entries
     */
    public function __construct(
        public string $path,
        public ?string $parentPath,
        public array $entries,
    ) {}

    /**
     * The trail from the media root down to the current directory.
     *
     * @return array<int, array{name: string, path: string}>
     */
    public function breadcrumbs(): array
    {
        $breadcrumbs = [['name' => 'Медіа', 'path' => '']];

        $walked = [];

        foreach (array_filter(explode('/', $this->path)) as $segment) {
            $walked[] = $segment;

            $breadcrumbs[] = ['name' => $segment, 'path' => implode('/', $walked)];
        }

        return $breadcrumbs;
    }

    /**
     * @return array{path: string, parentPath: string|null, breadcrumbs: array<int, array{name: string, path: string}>, entries: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'parentPath' => $this->parentPath,
            'breadcrumbs' => $this->breadcrumbs(),
            'entries' => array_map(static fn (MediaEntry $entry): array => $entry->toArray(), $this->entries),
        ];
    }
}

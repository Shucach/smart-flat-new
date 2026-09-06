<?php

use App\Modules\Media\Exceptions\MediaPathException;
use App\Modules\Media\Services\LocalMediaLibrary;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/smartflat-media-'.bin2hex(random_bytes(6));
    $this->outside = sys_get_temp_dir().'/smartflat-outside-'.bin2hex(random_bytes(6));

    $files = new Filesystem;
    $files->makeDirectory($this->root.'/Серіали/Сезон 1', 0777, true);
    $files->makeDirectory($this->outside, 0777, true);
    $files->put($this->root.'/Серіали/Сезон 1/s01e01.mkv', 'episode');
    $files->put($this->root.'/film.mkv', 'film');
    $files->put($this->root.'/film.torrent', 'torrent');
    $files->put($this->root.'/.hidden', 'hidden');
    $files->put($this->outside.'/secret.txt', 'secret');

    $this->library = new LocalMediaLibrary($files, $this->root, ['torrent']);
});

afterEach(function () {
    $files = new Filesystem;
    $files->deleteDirectory($this->root);
    $files->deleteDirectory($this->outside);
});

it('lists directories before files and skips hidden entries and ignored extensions', function () {
    $listing = $this->library->list('');

    expect(array_map(fn ($entry) => $entry->name, $listing->entries))->toBe(['Серіали', 'film.mkv'])
        ->and($listing->parentPath)->toBeNull()
        ->and($listing->path)->toBe('');
});

it('reports the size and the modification time of a file', function () {
    $entry = $this->library->list('')->entries[1];

    expect($entry->isDirectory)->toBeFalse()
        ->and($entry->size)->toBe(4)
        ->and($entry->modifiedAt)->not->toBeNull()
        ->and($entry->toArray()['sizeForHumans'])->toBe('4 B');
});

it('exposes the parent path and the breadcrumbs of a nested directory', function () {
    $listing = $this->library->list('Серіали/Сезон 1');

    expect($listing->parentPath)->toBe('Серіали')
        ->and($listing->breadcrumbs())->toBe([
            ['name' => 'Медіа', 'path' => ''],
            ['name' => 'Серіали', 'path' => 'Серіали'],
            ['name' => 'Сезон 1', 'path' => 'Серіали/Сезон 1'],
        ]);
});

it('rejects a path that escapes the media root', function (string $path) {
    expect(fn () => $this->library->list($path))->toThrow(MediaPathException::class);
})->with([
    'parent traversal' => '../',
    'deep traversal' => '../../etc',
    'traversal inside a segment' => 'Серіали/../../',
    'null byte' => "Серіали\0",
]);

it('does not delete a file that lives outside the media root', function () {
    expect(fn () => $this->library->delete('../'.basename($this->outside).'/secret.txt'))
        ->toThrow(MediaPathException::class);

    expect(file_exists($this->outside.'/secret.txt'))->toBeTrue();
});

it('deletes a directory with its contents', function () {
    $this->library->delete('Серіали');

    expect(is_dir($this->root.'/Серіали'))->toBeFalse()
        ->and(is_file($this->root.'/film.mkv'))->toBeTrue();
});

it('deletes a single file', function () {
    $this->library->delete('film.mkv');

    expect(is_file($this->root.'/film.mkv'))->toBeFalse();
});

it('refuses to delete the media root itself', function () {
    expect(fn () => $this->library->delete(''))->toThrow(MediaPathException::class);

    expect(is_dir($this->root))->toBeTrue();
});

it('throws when the requested path does not exist', function () {
    expect(fn () => $this->library->list('missing'))->toThrow(MediaPathException::class);
});

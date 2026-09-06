<?php

use App\Enums\FrameUploadKind;
use App\Modules\Frame\Exceptions\FrameException;
use App\Modules\Frame\Services\HttpFrameGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->gateway = new HttpFrameGateway('http://frame.test', 'secret-key', 5);
});

it('lists the images of a page', function () {
    Http::fake([
        'frame.test/api/v1/list-images*' => Http::response([
            'success' => true,
            'message' => null,
            'data' => [
                'images' => [
                    ['name' => 'one.jpg', 'file' => 'data:image/jpeg;base64,AAA'],
                    ['name' => 'two.mp4', 'file' => 'data:image/jpeg;base64,BBB', 'type' => 'video'],
                ],
                'pagination' => ['page' => 2, 'prePage' => 2, 'maxPage' => 2, 'total' => 3],
            ],
        ]),
    ]);

    $page = $this->gateway->list(2, 2);

    expect($page->page)->toBe(2)
        ->and($page->perPage)->toBe(2)
        ->and($page->lastPage)->toBe(2)
        ->and($page->total)->toBe(3)
        ->and($page->images)->toHaveCount(2)
        ->and($page->images[0]->name)->toBe('one.jpg')
        ->and($page->images[0]->preview)->toBe('data:image/jpeg;base64,AAA')
        ->and($page->images[0]->kind)->toBe(FrameUploadKind::Image);

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'secret-key')
        && str_contains($request->url(), 'page=2')
        && str_contains($request->url(), 'prePage=2'));
});

it('sends the image names as a json array when deleting', function () {
    Http::fake([
        'frame.test/api/v1/delete-images' => Http::response(['success' => true, 'message' => null, 'data' => []]),
    ]);

    $this->gateway->delete([1 => 'one.jpg', 5 => 'two.jpg']);

    Http::assertSent(fn (Request $request) => $request->url() === 'http://frame.test/api/v1/delete-images'
        && $request->isJson()
        && $request->data() === ['images' => ['one.jpg', 'two.jpg']]);
});

it('uploads the file contents as multipart and returns the stored name', function () {
    Http::fake([
        'frame.test/api/v1/upload-images' => Http::response([
            'success' => true,
            'message' => null,
            'data' => ['images' => ['1788690163183_holiday.jpg'], 'rejected' => []],
        ]),
    ]);

    $path = tempnam(sys_get_temp_dir(), 'frame');
    file_put_contents($path, 'binary-content');

    expect($this->gateway->upload($path, 'holiday.jpg'))->toBe('1788690163183_holiday.jpg');

    Http::assertSent(fn (Request $request) => $request->url() === 'http://frame.test/api/v1/upload-images'
        && $request->isMultipart()
        && $request->data()[0]['name'] === 'images'
        && $request->data()[0]['filename'] === 'holiday.jpg');

    unlink($path);
});

it('repeats the reason the frame refused a clip', function () {
    Http::fake([
        'frame.test/api/v1/upload-images' => Http::response([
            'success' => false,
            'message' => 'Images can not be processed',
            'data' => [
                'images' => [],
                'rejected' => [
                    ['name' => 'IMG_4410.MOV', 'reason' => 'video is HEVC (H.265); the frame only plays H.264'],
                ],
            ],
        ]),
    ]);

    $path = tempnam(sys_get_temp_dir(), 'frame');
    file_put_contents($path, 'binary-content');

    expect(fn () => $this->gateway->upload($path, 'IMG_4410.MOV'))
        ->toThrow(FrameException::class, 'Рамка не прийняла файл: video is HEVC (H.265); the frame only plays H.264');

    unlink($path);
});

it('marks a refusal as permanent so the queue stops retrying it', function () {
    Http::fake([
        'frame.test/api/v1/upload-images' => Http::response([
            'success' => true,
            'message' => null,
            'data' => ['images' => [], 'rejected' => [['name' => 'clip.mp4', 'reason' => 'clip is 400s long']]],
        ]),
    ]);

    $path = tempnam(sys_get_temp_dir(), 'frame');
    file_put_contents($path, 'binary-content');

    try {
        $this->gateway->upload($path, 'clip.mp4');
    } catch (FrameException $exception) {
        expect($exception->permanent)->toBeTrue()
            ->and($exception->getMessage())->toContain('clip is 400s long');
    }

    unlink($path);
});

it('posts to the restart endpoint to reload what the panel shows', function () {
    Http::fake([
        'frame.test/api/v1/restart-slideshow' => Http::response([
            'success' => true,
            'message' => null,
            'data' => ['active' => true, 'state' => 'active'],
        ]),
    ]);

    $this->gateway->restartSlideshow();

    Http::assertSent(fn (Request $request) => $request->url() === 'http://frame.test/api/v1/restart-slideshow'
        && $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'secret-key'));
});

it('throws when the frame reports the slideshow could not be restarted', function () {
    Http::fake([
        'frame.test/api/v1/restart-slideshow' => Http::response([
            'success' => false,
            'message' => 'slideshowctl: failed to take the console',
            'data' => [],
        ]),
    ]);

    expect(fn () => $this->gateway->restartSlideshow())
        ->toThrow(FrameException::class, 'slideshowctl: failed to take the console');
});

it('throws when the frame reports a failure', function () {
    Http::fake([
        'frame.test/api/v1/list-images*' => Http::response([
            'success' => false,
            'message' => 'Upload directory does not exist',
            'data' => [],
        ]),
    ]);

    expect(fn () => $this->gateway->list(1, 5))
        ->toThrow(FrameException::class, 'Upload directory does not exist');
});

it('throws when the frame cannot be reached', function () {
    Http::fake(['frame.test/*' => Http::failedConnection()]);

    expect(fn () => $this->gateway->list(1, 5))->toThrow(FrameException::class);
});

it('throws when the frame host is not configured', function () {
    expect(fn () => (new HttpFrameGateway('', '', 5))->list(1, 5))
        ->toThrow(FrameException::class, 'Розумну рамку не налаштовано.');
});

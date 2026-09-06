<?php

use App\Enums\Permission;
use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Exceptions\FrameException;
use App\Modules\Frame\Jobs\UploadFrameImage;
use App\Modules\Frame\Services\NullFrameGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

describe('index', function () {
    it('renders the requested page of the gallery', function () {
        $this->app->instance(FrameGateway::class, new NullFrameGateway(['a.jpg', 'b.jpg', 'c.jpg']));

        $this->actingAs(userWithPermissions(Permission::FrameView))
            ->get(route('frame.index', ['page' => 2, 'perPage' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('frame/Index')
                ->has('images', 1)
                ->where('images.0.name', 'c.jpg')
                ->where('images.0.preview', fn (string $preview) => str_starts_with($preview, 'data:image/jpeg;base64,'))
                ->where('pagination', ['page' => 2, 'perPage' => 2, 'lastPage' => 2, 'total' => 3]));
    });

    it('returns 403 for a user without the frame.view permission', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView))
            ->get(route('frame.index'))
            ->assertForbidden();
    });

    it('rejects a page size above the allowed maximum', function () {
        $this->actingAs(userWithPermissions(Permission::FrameView))
            ->get(route('frame.index', ['perPage' => 999]))
            ->assertSessionHasErrors('perPage');
    });
});

describe('store', function () {
    it('queues one upload job per image and keeps the file until the job runs', function () {
        Storage::fake('local');
        Queue::fake([UploadFrameImage::class]);

        $this->actingAs(userWithPermissions(Permission::FrameView, Permission::FrameUpload))
            ->from(route('frame.index'))
            ->post(route('frame.store'), [
                'images' => [
                    UploadedFile::fake()->image('first.jpg'),
                    UploadedFile::fake()->image('second.png'),
                ],
            ])
            ->assertRedirect(route('frame.index'));

        Queue::assertPushed(UploadFrameImage::class, 2);
        Queue::assertPushed(fn (UploadFrameImage $job) => $job->originalName === 'first.jpg'
            && Storage::disk('local')->exists($job->storedPath));
    });

    it('rejects a file that is not an image', function () {
        Queue::fake([UploadFrameImage::class]);

        $this->actingAs(userWithPermissions(Permission::FrameUpload))
            ->from(route('dashboard'))
            ->post(route('frame.store'), ['images' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors(['images.0' => 'Завантажувати можна лише зображення.']);

        Queue::assertNothingPushed();
    });

    it('returns 403 for a user without the frame.upload permission', function () {
        Queue::fake([UploadFrameImage::class]);

        $this->actingAs(userWithPermissions(Permission::FrameView))
            ->post(route('frame.store'), ['images' => [UploadedFile::fake()->image('first.jpg')]])
            ->assertForbidden();

        Queue::assertNothingPushed();
    });
});

describe('destroy', function () {
    it('removes the selected images from the frame', function () {
        $gateway = new NullFrameGateway(['a.jpg', 'b.jpg']);
        $this->app->instance(FrameGateway::class, $gateway);

        $this->actingAs(userWithPermissions(Permission::FrameView, Permission::FrameDelete))
            ->from(route('frame.index'))
            ->delete(route('frame.destroy'), ['names' => ['a.jpg']])
            ->assertRedirect(route('frame.index'));

        expect(array_map(fn ($image) => $image->name, $gateway->list(1, 10)->images))->toBe(['b.jpg']);
    });

    it('flashes the reason when the frame refuses to delete', function () {
        $gateway = Mockery::mock(FrameGateway::class);
        $gateway->shouldReceive('delete')->once()->andThrow(FrameException::unavailable('images is required'));
        $this->app->instance(FrameGateway::class, $gateway);

        $this->actingAs(userWithPermissions(Permission::FrameView, Permission::FrameDelete))
            ->from(route('frame.index'))
            ->delete(route('frame.destroy'), ['names' => ['a.jpg']])
            ->assertRedirect(route('frame.index'))
            ->assertSessionHasErrors(['names' => 'Розумна рамка недоступна: images is required'])
            ->assertSessionHas(SessionKey::FLASH_DATA, [
                'toast' => ['type' => 'error', 'message' => 'Розумна рамка недоступна: images is required'],
            ]);
    });

    it('rejects a name that points outside the gallery', function () {
        $gateway = new NullFrameGateway(['a.jpg']);
        $this->app->instance(FrameGateway::class, $gateway);

        $this->actingAs(userWithPermissions(Permission::FrameDelete))
            ->from(route('dashboard'))
            ->delete(route('frame.destroy'), ['names' => ['../../etc/passwd']])
            ->assertSessionHasErrors(['names.0' => 'Некоректна назва зображення.']);

        expect($gateway->list(1, 10)->total)->toBe(1);
    });

    it('returns 403 for a user without the frame.delete permission', function () {
        $gateway = new NullFrameGateway(['a.jpg']);
        $this->app->instance(FrameGateway::class, $gateway);

        $this->actingAs(userWithPermissions(Permission::FrameView))
            ->delete(route('frame.destroy'), ['names' => ['a.jpg']])
            ->assertForbidden();

        expect($gateway->list(1, 10)->total)->toBe(1);
    });
});

describe('restart', function () {
    it('restarts the slideshow so the frame picks up the new images', function () {
        $gateway = new NullFrameGateway(['a.jpg']);
        $this->app->instance(FrameGateway::class, $gateway);

        $this->actingAs(userWithPermissions(Permission::FrameView, Permission::FrameRestart))
            ->from(route('frame.index'))
            ->post(route('frame.restart'))
            ->assertRedirect(route('frame.index'))
            ->assertSessionHas(SessionKey::FLASH_DATA, [
                'toast' => ['type' => 'success', 'message' => 'Показ перезапущено — рамка вже з новими фото.'],
            ]);

        expect($gateway->restarts())->toBe(1);
    });

    it('flashes the reason when the frame refuses to restart', function () {
        $gateway = Mockery::mock(FrameGateway::class);
        $gateway->shouldReceive('restartSlideshow')->once()->andThrow(FrameException::unavailable('slideshow is down'));
        $this->app->instance(FrameGateway::class, $gateway);

        $this->actingAs(userWithPermissions(Permission::FrameView, Permission::FrameRestart))
            ->from(route('frame.index'))
            ->post(route('frame.restart'))
            ->assertRedirect(route('frame.index'))
            ->assertSessionHas(SessionKey::FLASH_DATA, [
                'toast' => ['type' => 'error', 'message' => 'Розумна рамка недоступна: slideshow is down'],
            ]);
    });

    it('returns 403 for a user without the frame.restart permission', function () {
        $gateway = new NullFrameGateway(['a.jpg']);
        $this->app->instance(FrameGateway::class, $gateway);

        $this->actingAs(userWithPermissions(Permission::FrameView, Permission::FrameUpload))
            ->post(route('frame.restart'))
            ->assertForbidden();

        expect($gateway->restarts())->toBe(0);
    });
});

it('deletes the temporary file after the upload job succeeds', function () {
    Storage::fake('local');
    $gateway = new NullFrameGateway([]);
    $this->app->instance(FrameGateway::class, $gateway);

    Storage::disk('local')->put('frame-uploads/tmp.jpg', 'content');

    (new UploadFrameImage('frame-uploads/tmp.jpg', 'holiday.jpg'))->handle($gateway);

    expect(Storage::disk('local')->exists('frame-uploads/tmp.jpg'))->toBeFalse()
        ->and(array_map(fn ($image) => $image->name, $gateway->list(1, 10)->images))->toBe(['holiday.jpg']);
});

it('deletes the temporary file when the upload job fails for good', function () {
    Storage::fake('local');
    Storage::disk('local')->put('frame-uploads/tmp.jpg', 'content');

    (new UploadFrameImage('frame-uploads/tmp.jpg', 'holiday.jpg'))->failed(new RuntimeException('frame is down'));

    expect(Storage::disk('local')->exists('frame-uploads/tmp.jpg'))->toBeFalse();
});

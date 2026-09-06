<?php

use App\Enums\FrameUploadKind;
use App\Enums\FrameUploadStatus;
use App\Enums\Permission;
use App\Models\FrameUpload;
use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Exceptions\FrameException;
use App\Modules\Frame\Exceptions\TranscodeException;
use App\Modules\Frame\Jobs\UploadFrameImage;
use App\Modules\Frame\Jobs\UploadFrameVideo;
use App\Modules\Frame\Services\NullFrameGateway;
use App\Modules\Frame\Services\NullVideoTranscoder;
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
                ->where('images.0.kind', 'image')
                ->where('images.0.preview', fn (string $preview) => str_starts_with($preview, 'data:image/jpeg;base64,'))
                ->where('pagination', ['page' => 2, 'perPage' => 2, 'lastPage' => 2, 'total' => 3]));
    });

    it('reports what the queue is still working on', function () {
        $this->app->instance(FrameGateway::class, new NullFrameGateway([]));

        FrameUpload::factory()->transcoding(35)->create(['original_name' => 'birthday.mov']);
        FrameUpload::factory()->failed('Рамка не прийняла файл: video is HEVC (H.265)')->create();

        $this->actingAs(userWithPermissions(Permission::FrameView))
            ->get(route('frame.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('uploads', 2)
                ->where('uploads.1.name', 'birthday.mov')
                ->where('uploads.1.kind', 'video')
                ->where('uploads.1.status', 'transcoding')
                ->where('uploads.1.statusLabel', 'Перекодування відео')
                ->where('uploads.1.progress', 35)
                ->where('uploads.0.status', 'failed')
                ->where('uploads.0.message', 'Рамка не прийняла файл: video is HEVC (H.265)')
                ->where('limits.videoSeconds', 60)
                ->etc());
    });

    it('leaves out an upload that finished long ago', function () {
        $this->app->instance(FrameGateway::class, new NullFrameGateway([]));

        FrameUpload::factory()->completed()->create([
            'finished_at' => now()->subMinutes(FrameUpload::VISIBLE_MINUTES + 1),
        ]);

        $this->actingAs(userWithPermissions(Permission::FrameView))
            ->get(route('frame.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('uploads', 0)->etc());
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
    it('queues one job per file and records what the queue is doing with it', function () {
        Storage::fake('local');
        Queue::fake([UploadFrameImage::class, UploadFrameVideo::class]);

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
        Queue::assertPushed(fn (UploadFrameImage $job) => $job->upload->original_name === 'first.jpg'
            && $job->upload->kind === FrameUploadKind::Image
            && $job->upload->status === FrameUploadStatus::Queued
            && Storage::disk('local')->exists((string) $job->upload->stored_path));

        expect(FrameUpload::count())->toBe(2);
    });

    it('sends a clip to the transcoding job rather than straight to the frame', function () {
        Storage::fake('local');
        Queue::fake([UploadFrameImage::class, UploadFrameVideo::class]);

        $this->actingAs(userWithPermissions(Permission::FrameView, Permission::FrameUpload))
            ->from(route('frame.index'))
            ->post(route('frame.store'), [
                'images' => [UploadedFile::fake()->create('birthday.mov', 2048, 'video/quicktime')],
            ])
            ->assertRedirect(route('frame.index'))
            ->assertSessionHas(SessionKey::FLASH_DATA, [
                'toast' => [
                    'type' => 'success',
                    'message' => 'Файли в черзі. Відео спершу перекодується — це може зайняти кілька хвилин.',
                ],
            ]);

        Queue::assertNotPushed(UploadFrameImage::class);
        Queue::assertPushed(fn (UploadFrameVideo $job) => $job->upload->kind === FrameUploadKind::Video
            && $job->upload->original_name === 'birthday.mov');
    });

    it('rejects a file that is neither a picture nor a clip', function () {
        Queue::fake([UploadFrameImage::class, UploadFrameVideo::class]);

        $this->actingAs(userWithPermissions(Permission::FrameUpload))
            ->from(route('dashboard'))
            ->post(route('frame.store'), ['images' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors(['images.0' => 'Підтримуються фото JPG, PNG, WEBP та відео MP4, MOV, M4V.']);

        Queue::assertNothingPushed();
        expect(FrameUpload::count())->toBe(0);
    });

    it('holds a clip to the video size limit rather than the picture one', function () {
        Queue::fake([UploadFrameImage::class, UploadFrameVideo::class]);
        config(['smartflat.frame.video.max_upload_kilobytes' => 51200]);

        $this->actingAs(userWithPermissions(Permission::FrameUpload))
            ->from(route('dashboard'))
            ->post(route('frame.store'), [
                'images' => [
                    // Well past the 20 MB ceiling a photograph is held to.
                    UploadedFile::fake()->create('short.mp4', 30720, 'video/mp4'),
                    UploadedFile::fake()->create('long.mp4', 61440, 'video/mp4'),
                ],
            ])
            ->assertSessionHasErrors(['images.1' => 'Розмір відео не може перевищувати 50 МБ.'])
            ->assertSessionDoesntHaveErrors('images.0');
    });

    it('returns 403 for a user without the frame.upload permission', function () {
        Queue::fake([UploadFrameImage::class, UploadFrameVideo::class]);

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

describe('jobs', function () {
    it('uploads a picture and closes the row out with the name the frame gave it', function () {
        Storage::fake('local');
        $gateway = new NullFrameGateway([]);

        Storage::disk('local')->put('frame-uploads/tmp.jpg', 'content');
        $upload = FrameUpload::factory()->create([
            'original_name' => 'holiday.jpg',
            'stored_path' => 'frame-uploads/tmp.jpg',
        ]);

        (new UploadFrameImage($upload))->handle($gateway);

        expect(Storage::disk('local')->exists('frame-uploads/tmp.jpg'))->toBeFalse()
            ->and($upload->refresh()->status)->toBe(FrameUploadStatus::Completed)
            ->and($upload->progress)->toBe(100)
            ->and($upload->frame_name)->toBe('holiday.jpg')
            ->and(array_map(fn ($image) => $image->name, $gateway->list(1, 10)->images))->toBe(['holiday.jpg']);
    });

    it('transcodes a clip before sending it and reports how far along it is', function () {
        Storage::fake('local');
        $gateway = new NullFrameGateway([]);
        $transcoder = new NullVideoTranscoder;

        Storage::disk('local')->put('frame-uploads/tmp.mov', 'clip-bytes');
        $upload = FrameUpload::factory()->video()->create([
            'original_name' => 'birthday.mov',
            'stored_path' => 'frame-uploads/tmp.mov',
        ]);

        (new UploadFrameVideo($upload))->handle($gateway, $transcoder);

        expect($transcoder->calls())->toHaveCount(1)
            ->and($transcoder->calls()[0]['target'])->toContain('frame-uploads/encoded/'.$upload->id.'.mp4')
            // The frame only stores MP4, so a phone's .mov is renamed on the way out.
            ->and(array_map(fn ($image) => $image->name, $gateway->list(1, 10)->images))->toBe(['birthday.mp4'])
            ->and($upload->refresh()->status)->toBe(FrameUploadStatus::Completed)
            ->and($upload->progress)->toBe(100);
    });

    it('leaves nothing on disk once a clip has been sent', function () {
        Storage::fake('local');

        Storage::disk('local')->put('frame-uploads/tmp.mov', 'clip-bytes');
        $upload = FrameUpload::factory()->video()->create(['stored_path' => 'frame-uploads/tmp.mov']);

        (new UploadFrameVideo($upload))->handle(new NullFrameGateway([]), new NullVideoTranscoder);

        expect(Storage::disk('local')->exists('frame-uploads/tmp.mov'))->toBeFalse()
            ->and(Storage::disk('local')->exists('frame-uploads/encoded/'.$upload->id.'.mp4'))->toBeFalse();
    });

    it('records the reason a clip could not be converted', function () {
        Storage::fake('local');
        Storage::disk('local')->put('frame-uploads/tmp.mov', 'clip-bytes');
        $upload = FrameUpload::factory()->video()->create(['stored_path' => 'frame-uploads/tmp.mov']);

        (new UploadFrameVideo($upload))->failed(TranscodeException::tooLong(93.4, 60));

        expect($upload->refresh()->status)->toBe(FrameUploadStatus::Failed)
            ->and($upload->message)->toBe('Ролик триває 94 с, а максимум — 60 с.')
            ->and(Storage::disk('local')->exists('frame-uploads/tmp.mov'))->toBeFalse();
    });

    it('stops retrying once the frame has read the file and refused it', function () {
        Storage::fake('local');
        Storage::disk('local')->put('frame-uploads/tmp.jpg', 'content');

        $upload = FrameUpload::factory()->create(['stored_path' => 'frame-uploads/tmp.jpg']);
        $gateway = Mockery::mock(FrameGateway::class);
        $gateway->shouldReceive('upload')->once()
            ->andThrow(FrameException::rejected('video is HEVC (H.265); the frame only plays H.264'));

        $job = new UploadFrameImage($upload);
        $job->handle($gateway);

        expect($upload->refresh()->status)->toBe(FrameUploadStatus::Failed)
            ->and($upload->message)->toContain('the frame only plays H.264');
    });

    it('lets an unreachable frame be retried', function () {
        Storage::fake('local');
        Storage::disk('local')->put('frame-uploads/tmp.jpg', 'content');

        $upload = FrameUpload::factory()->create(['stored_path' => 'frame-uploads/tmp.jpg']);
        $gateway = Mockery::mock(FrameGateway::class);
        $gateway->shouldReceive('upload')->once()->andThrow(FrameException::unavailable('connection refused'));

        expect(fn () => (new UploadFrameImage($upload))->handle($gateway))
            ->toThrow(FrameException::class, 'connection refused');

        expect($upload->refresh()->status)->toBe(FrameUploadStatus::Uploading)
            ->and(Storage::disk('local')->exists('frame-uploads/tmp.jpg'))->toBeTrue();
    });

    it('closes the row out when the temporary file has gone missing', function () {
        Storage::fake('local');
        $upload = FrameUpload::factory()->create(['stored_path' => 'frame-uploads/gone.jpg']);

        (new UploadFrameImage($upload))->handle(new NullFrameGateway([]));

        expect($upload->refresh()->status)->toBe(FrameUploadStatus::Failed)
            ->and($upload->message)->toBe('Тимчасовий файл зник до обробки.');
    });
});

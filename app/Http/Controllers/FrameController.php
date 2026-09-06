<?php

namespace App\Http\Controllers;

use App\Enums\FrameUploadKind;
use App\Enums\FrameUploadStatus;
use App\Http\Requests\Frame\FrameDestroyRequest;
use App\Http\Requests\Frame\FrameIndexRequest;
use App\Http\Requests\Frame\FrameStoreRequest;
use App\Models\FrameUpload;
use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Data\FramePage;
use App\Modules\Frame\Exceptions\FrameException;
use App\Modules\Frame\Jobs\UploadFrameImage;
use App\Modules\Frame\Jobs\UploadFrameVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class FrameController extends Controller
{
    private const string UPLOAD_DIRECTORY = 'frame-uploads';

    public function index(FrameIndexRequest $request, FrameGateway $frame): Response
    {
        $page = $request->page();
        $perPage = $request->perPage();

        try {
            $framePage = $frame->list($page, $perPage);
        } catch (FrameException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            $framePage = new FramePage([], $page, $perPage, 1, 0);
        }

        return Inertia::render('frame/Index', [
            ...$framePage->toArray(),
            'uploads' => $this->uploadsInFlight(),
            'limits' => [
                'videoSeconds' => (int) config('smartflat.frame.video.max_seconds'),
                'videoMegabytes' => (int) round((int) config('smartflat.frame.video.max_upload_kilobytes') / 1024),
            ],
        ]);
    }

    /**
     * Files are only parked on disk here. Everything slow -- converting a clip
     * into the H.264 the panel decodes, and pushing it over the network to the
     * frame -- happens on the queue, and the page reads its progress back out of
     * the `frame_uploads` rows this creates.
     */
    public function store(FrameStoreRequest $request): RedirectResponse
    {
        $videos = 0;

        /** @var UploadedFile $file */
        foreach ($request->file('images') as $file) {
            $storedPath = $file->store(self::UPLOAD_DIRECTORY, 'local');

            if ($storedPath === false) {
                return back()->withErrors(['images' => 'Не вдалося зберегти файл на сервері.']);
            }

            $kind = FrameUploadKind::forUpload($file);
            $upload = FrameUpload::create([
                'user_id' => $request->user()?->id,
                'original_name' => $file->getClientOriginalName(),
                'kind' => $kind,
                'status' => FrameUploadStatus::Queued,
                'progress' => 0,
                'stored_path' => $storedPath,
            ]);

            if ($kind === FrameUploadKind::Video) {
                $videos++;
                UploadFrameVideo::dispatch($upload);

                continue;
            }

            UploadFrameImage::dispatch($upload);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $videos > 0
                ? 'Файли в черзі. Відео спершу перекодується — це може зайняти кілька хвилин.'
                : 'Зображення поставлено в чергу на завантаження.',
        ]);

        return back();
    }

    /**
     * The panel keeps showing the picture list it read when its player started,
     * so uploaded and deleted images reach the screen only after this call.
     */
    public function restart(FrameGateway $frame): RedirectResponse
    {
        try {
            $frame->restartSlideshow();
        } catch (FrameException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Показ перезапущено — рамка вже з новими фото.']);

        return back();
    }

    public function destroy(FrameDestroyRequest $request, FrameGateway $frame): RedirectResponse
    {
        try {
            $frame->delete($request->names());
        } catch (FrameException $exception) {
            // The page has no form to hang the error on, so the reason is flashed
            // as a toast while the error keeps the selection on screen for a retry.
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back()->withErrors(['names' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Видалено.']);

        return back();
    }

    /**
     * What the queue is working on, plus what it finished recently enough that
     * the person who uploaded it is probably still looking at the page.
     *
     * @return array<int, array{id: int, name: string, kind: string, status: string, statusLabel: string, progress: int, message: string|null}>
     */
    private function uploadsInFlight(): array
    {
        return FrameUpload::query()
            ->visible()
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(static fn (FrameUpload $upload): array => [
                'id' => $upload->id,
                'name' => $upload->original_name,
                'kind' => $upload->kind->value,
                'status' => $upload->status->value,
                'statusLabel' => $upload->status->label(),
                'progress' => $upload->progress,
                'message' => $upload->message,
            ])
            ->all();
    }
}

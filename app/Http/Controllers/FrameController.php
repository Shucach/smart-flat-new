<?php

namespace App\Http\Controllers;

use App\Http\Requests\Frame\FrameDestroyRequest;
use App\Http\Requests\Frame\FrameIndexRequest;
use App\Http\Requests\Frame\FrameStoreRequest;
use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Data\FramePage;
use App\Modules\Frame\Exceptions\FrameException;
use App\Modules\Frame\Jobs\UploadFrameImage;
use Illuminate\Http\RedirectResponse;
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

        return Inertia::render('frame/Index', $framePage->toArray());
    }

    public function store(FrameStoreRequest $request): RedirectResponse
    {
        foreach ($request->file('images') as $image) {
            $storedPath = $image->store(self::UPLOAD_DIRECTORY, 'local');

            if ($storedPath === false) {
                return back()->withErrors(['images' => 'Не вдалося зберегти зображення на сервері.']);
            }

            UploadFrameImage::dispatch($storedPath, $image->getClientOriginalName());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Зображення поставлено в чергу на завантаження.']);

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
}

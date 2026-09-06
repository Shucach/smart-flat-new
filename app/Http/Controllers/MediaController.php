<?php

namespace App\Http\Controllers;

use App\Http\Requests\Media\MediaDestroyRequest;
use App\Http\Requests\Media\MediaIndexRequest;
use App\Modules\Media\Contracts\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(MediaIndexRequest $request, MediaLibrary $library): Response
    {
        return Inertia::render('media/Index', $library->list($request->path())->toArray());
    }

    public function destroy(MediaDestroyRequest $request, MediaLibrary $library): RedirectResponse
    {
        $library->delete($request->path());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Видалено.']);

        return back();
    }
}

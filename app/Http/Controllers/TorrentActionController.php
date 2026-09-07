<?php

namespace App\Http\Controllers;

use App\Http\Requests\Torrent\TorrentActionRequest;
use App\Modules\Torrent\Contracts\TorrentClient;
use App\Modules\Torrent\Exceptions\TorrentException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Start, stop, verify, reannounce and queue movement -- everything that changes
 * what a torrent is doing without adding or removing it.
 */
class TorrentActionController extends Controller
{
    public function __invoke(TorrentActionRequest $request, TorrentClient $torrents): RedirectResponse
    {
        $action = $request->action();

        try {
            $torrents->act($action, $request->ids());
        } catch (TorrentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back()->withErrors(['action' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $action->label().'.']);

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Torrent\UpdateTorrentFilesRequest;
use App\Modules\Torrent\Contracts\TorrentClient;
use App\Modules\Torrent\Exceptions\TorrentException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TorrentFileController extends Controller
{
    /**
     * Include or exclude files of a torrent, or move them between priorities.
     * Both can travel in one request, and the wanted flag is applied first so a
     * file that was just excluded is not given a priority it cannot use.
     */
    public function update(UpdateTorrentFilesRequest $request, TorrentClient $torrents, int $torrent): RedirectResponse
    {
        $indexes = $request->indexes();
        $wanted = $request->wanted();
        $priority = $request->priority();

        try {
            if ($wanted !== null) {
                $torrents->setFilesWanted($torrent, $indexes, $wanted);
            }

            if ($priority !== null) {
                $torrents->setFilePriority($torrent, $indexes, $priority);
            }
        } catch (TorrentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back()->withErrors(['indexes' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Файли оновлено.']);

        return back();
    }
}

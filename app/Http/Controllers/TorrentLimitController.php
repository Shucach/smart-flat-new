<?php

namespace App\Http\Controllers;

use App\Http\Requests\Torrent\UpdateTorrentLimitsRequest;
use App\Modules\Torrent\Contracts\TorrentClient;
use App\Modules\Torrent\Exceptions\TorrentException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TorrentLimitController extends Controller
{
    public function update(UpdateTorrentLimitsRequest $request, TorrentClient $torrents, int $torrent): RedirectResponse
    {
        try {
            $torrents->setLimits($torrent, $request->limits());
        } catch (TorrentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back()->withErrors(['downloadKilobytes' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Обмеження торента збережено.']);

        return back();
    }
}

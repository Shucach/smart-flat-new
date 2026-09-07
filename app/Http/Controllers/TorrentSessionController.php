<?php

namespace App\Http\Controllers;

use App\Http\Requests\Torrent\UpdateSessionSettingsRequest;
use App\Modules\Torrent\Contracts\TorrentClient;
use App\Modules\Torrent\Exceptions\TorrentException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The settings that apply to the whole daemon rather than to one torrent.
 */
class TorrentSessionController extends Controller
{
    public function update(UpdateSessionSettingsRequest $request, TorrentClient $torrents): RedirectResponse
    {
        try {
            $torrents->updateSession($request->settings());
        } catch (TorrentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back()->withErrors(['speedLimitDown' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Налаштування збережено.']);

        return back();
    }

    /**
     * Asks the daemon whether its listening port is reachable from outside.
     */
    public function testPort(TorrentClient $torrents): RedirectResponse
    {
        try {
            $isOpen = $torrents->testPort();
        } catch (TorrentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => $isOpen ? 'success' : 'error',
            'message' => $isOpen ? 'Порт відкритий.' : 'Порт закритий — вхідні підключення не проходять.',
        ]);

        return back();
    }
}

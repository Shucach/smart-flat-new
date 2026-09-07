<?php

namespace App\Http\Controllers;

use App\Http\Requests\Torrent\DestroyTorrentRequest;
use App\Http\Requests\Torrent\StoreTorrentRequest;
use App\Http\Requests\Torrent\TorrentIndexRequest;
use App\Modules\Torrent\Contracts\TorrentClient;
use App\Modules\Torrent\Data\TorrentOverview;
use App\Modules\Torrent\Exceptions\TorrentException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TorrentController extends Controller
{
    /**
     * The daemon is asked once per request, however many props are read back
     * out of it: the page polls `torrents` and `stats` together every few
     * seconds, and each of those would otherwise be a round trip of its own.
     */
    private ?TorrentOverview $overview = null;

    private ?string $failure = null;

    public function index(TorrentIndexRequest $request, TorrentClient $torrents): Response
    {
        $selected = $request->selected();

        return Inertia::render('torrents/Index', [
            'torrents' => fn (): array => $this->overview($torrents)?->toArray()['torrents'] ?? [],
            'stats' => fn (): ?array => $this->overview($torrents)?->stats->toArray(),
            'settings' => fn (): ?array => $this->overview($torrents)?->settings->toArray(),
            'error' => fn (): ?string => $this->overview($torrents) === null ? $this->failure : null,

            /*
             * The file list of a torrent is worth kilobytes per row, so it is
             * read only while a detail panel is open -- which the `selected`
             * query parameter says, and which survives the redirect back from
             * every action the panel offers.
             */
            'torrent' => fn (): ?array => $selected === null
                ? null
                : $this->detail($torrents, $selected),

            'limits' => [
                'maxFileMegabytes' => (int) round((int) config('smartflat.torrent.max_file_kilobytes') / 1024),
            ],
        ]);
    }

    public function store(StoreTorrentRequest $request, TorrentClient $torrents): RedirectResponse
    {
        $file = $request->torrentFile();
        $metainfo = $file?->get();

        if ($file !== null && $metainfo === false) {
            return back()->withErrors(['file' => 'Не вдалося прочитати файл.']);
        }

        try {
            $name = $metainfo === null
                ? $torrents->addUrl($request->url() ?? '', $request->downloadDir(), $request->paused())
                : $torrents->addMetainfo($metainfo, $request->downloadDir(), $request->paused());
        } catch (TorrentException $exception) {
            return back()->withErrors(['url' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Додано: {$name}"]);

        return back();
    }

    public function destroy(DestroyTorrentRequest $request, TorrentClient $torrents): RedirectResponse
    {
        $ids = $request->ids();

        try {
            $torrents->remove($ids, $request->deletesData());
        } catch (TorrentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back()->withErrors(['ids' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->deletesData()
                ? 'Видалено разом із завантаженими файлами.'
                : 'Видалено зі списку. Файли залишились на диску.',
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function detail(TorrentClient $torrents, int $id): ?array
    {
        try {
            return $torrents->detail($id)->toArray();
        } catch (TorrentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * Null once the daemon has refused this request; `$this->failure` says why.
     */
    private function overview(TorrentClient $torrents): ?TorrentOverview
    {
        if ($this->overview !== null || $this->failure !== null) {
            return $this->overview;
        }

        try {
            return $this->overview = $torrents->overview();
        } catch (TorrentException $exception) {
            $this->failure = $exception->getMessage();

            return null;
        }
    }
}

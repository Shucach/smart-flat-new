<?php

namespace App\Modules\Torrent\Services;

use App\Enums\TorrentAction;
use App\Enums\TorrentFilePriority;
use App\Enums\TorrentStatus;
use App\Modules\Torrent\Contracts\TorrentClient;
use App\Modules\Torrent\Data\SessionSettings;
use App\Modules\Torrent\Data\SessionStats;
use App\Modules\Torrent\Data\Torrent;
use App\Modules\Torrent\Data\TorrentDetail;
use App\Modules\Torrent\Data\TorrentFile;
use App\Modules\Torrent\Data\TorrentLimits;
use App\Modules\Torrent\Data\TorrentOverview;
use App\Modules\Torrent\Data\TorrentPeer;
use App\Modules\Torrent\Data\TorrentTracker;
use App\Modules\Torrent\Exceptions\TorrentException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Transmission's JSON-RPC endpoint, as described at
 * https://github.com/transmission/transmission/blob/main/docs/rpc-spec.md
 */
class TransmissionTorrentClient implements TorrentClient
{
    /**
     * The fields a list row needs. Files and peers are deliberately absent:
     * asking for them over thirty-odd torrents turns a poll into megabytes.
     *
     * @var array<int, string>
     */
    private const array LIST_FIELDS = [
        'id', 'hashString', 'name', 'status', 'percentDone', 'recheckProgress',
        'metadataPercentComplete', 'rateDownload', 'rateUpload', 'eta', 'uploadRatio',
        'totalSize', 'sizeWhenDone', 'leftUntilDone', 'downloadedEver', 'uploadedEver',
        'peersConnected', 'peersSendingToUs', 'peersGettingFromUs', 'queuePosition',
        'isStalled', 'isFinished', 'downloadDir', 'error', 'errorString',
        'addedDate', 'doneDate', 'labels',
    ];

    /**
     * @var array<int, string>
     */
    private const array DETAIL_FIELDS = [
        'files', 'fileStats', 'trackerStats', 'peers', 'comment', 'creator',
        'magnetLink', 'activityDate', 'pieceCount', 'pieceSize',
        'downloadLimit', 'downloadLimited', 'uploadLimit', 'uploadLimited',
        'seedRatioLimit', 'seedRatioMode', 'honorsSessionLimits',
    ];

    /**
     * Transmission answers the first request of a session with 409 and the
     * session id it expects from then on, as its CSRF defence.
     */
    private ?string $sessionId = null;

    public function __construct(
        private readonly string $url,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeout = 10,
    ) {}

    public function overview(): TorrentOverview
    {
        $torrents = $this->request('torrent-get', ['fields' => self::LIST_FIELDS]);
        $settings = $this->readSessionSettings();
        $stats = $this->request('session-stats');

        return new TorrentOverview(
            torrents: array_map(
                fn (array $torrent): Torrent => $this->toTorrent($torrent),
                array_values(array_filter($torrents['torrents'] ?? [], is_array(...))),
            ),
            stats: $this->toStats($stats, $settings),
            settings: $this->toSettings($settings),
        );
    }

    public function detail(int $id): TorrentDetail
    {
        $payload = $this->request('torrent-get', [
            'ids' => [$id],
            'fields' => [...self::LIST_FIELDS, ...self::DETAIL_FIELDS],
        ]);

        $torrent = $payload['torrents'][0] ?? null;

        if (! is_array($torrent)) {
            throw TorrentException::missing($id);
        }

        $activityDate = (int) ($torrent['activityDate'] ?? 0);

        return new TorrentDetail(
            torrent: $this->toTorrent($torrent),
            files: $this->toFiles($torrent),
            trackers: $this->toTrackers($torrent),
            peers: $this->toPeers($torrent),
            limits: $this->toLimits($torrent),
            comment: $this->nonEmpty($torrent['comment'] ?? null),
            creator: $this->nonEmpty($torrent['creator'] ?? null),
            magnetLink: $this->nonEmpty($torrent['magnetLink'] ?? null),
            lastActivityAt: $activityDate > 0 ? Carbon::createFromTimestamp($activityDate) : null,
            pieceCount: (int) ($torrent['pieceCount'] ?? 0),
            pieceSizeBytes: (int) ($torrent['pieceSize'] ?? 0),
        );
    }

    public function addUrl(string $url, ?string $downloadDir = null, bool $paused = false): string
    {
        return $this->add(['filename' => $url], $downloadDir, $paused);
    }

    public function addMetainfo(string $contents, ?string $downloadDir = null, bool $paused = false): string
    {
        return $this->add(['metainfo' => base64_encode($contents)], $downloadDir, $paused);
    }

    public function remove(array $ids, bool $deleteData): void
    {
        $this->request('torrent-remove', [
            'ids' => array_values($ids),
            'delete-local-data' => $deleteData,
        ]);
    }

    public function act(TorrentAction $action, array $ids): void
    {
        $this->request($action->rpcMethod(), ['ids' => array_values($ids)]);
    }

    public function setFilesWanted(int $id, array $indexes, bool $wanted): void
    {
        $this->request('torrent-set', [
            'ids' => [$id],
            ($wanted ? 'files-wanted' : 'files-unwanted') => array_values($indexes),
        ]);
    }

    public function setFilePriority(int $id, array $indexes, TorrentFilePriority $priority): void
    {
        $this->request('torrent-set', [
            'ids' => [$id],
            $priority->rpcArgument() => array_values($indexes),
        ]);
    }

    public function setLimits(int $id, TorrentLimits $limits): void
    {
        $this->request('torrent-set', [
            'ids' => [$id],
            'downloadLimited' => $limits->downloadKilobytes !== null,
            'downloadLimit' => $limits->downloadKilobytes ?? 0,
            'uploadLimited' => $limits->uploadKilobytes !== null,
            'uploadLimit' => $limits->uploadKilobytes ?? 0,
            'seedRatioMode' => match (true) {
                $limits->seedForever => 2,
                $limits->seedRatio !== null => 1,
                default => 0,
            },
            'seedRatioLimit' => $limits->seedRatio ?? 0,
            'honorsSessionLimits' => $limits->honorsSessionLimits,
        ]);
    }

    public function updateSession(SessionSettings $settings): void
    {
        $this->request('session-set', $settings->toRpcArguments());
    }

    public function testPort(): bool
    {
        return (bool) ($this->request('port-test')['port-is-open'] ?? false);
    }

    /**
     * @param  array{filename: string}|array{metainfo: string}  $source
     */
    private function add(array $source, ?string $downloadDir, bool $paused): string
    {
        $arguments = [...$source, 'paused' => $paused];

        if ($downloadDir !== null && $downloadDir !== '') {
            $arguments['download-dir'] = $downloadDir;
        }

        $payload = $this->request('torrent-add', $arguments);

        /**
         * A torrent the daemon already holds comes back under
         * `torrent-duplicate` with `result: success`, which is worth saying out
         * loud rather than reporting as a fresh addition.
         */
        if (is_array($payload['torrent-duplicate'] ?? null)) {
            throw TorrentException::rejected(sprintf(
                'торент «%s» вже додано',
                (string) ($payload['torrent-duplicate']['name'] ?? 'без назви'),
            ));
        }

        $added = $payload['torrent-added'] ?? null;

        if (! is_array($added)) {
            throw TorrentException::unavailable('несподівана відповідь на додавання торента');
        }

        return (string) ($added['name'] ?? 'торент');
    }

    /**
     * @return array<string, mixed>
     */
    private function readSessionSettings(): array
    {
        return $this->request('session-get');
    }

    /**
     * @param  array<string, mixed>  $torrent
     */
    private function toTorrent(array $torrent): Torrent
    {
        $eta = (int) ($torrent['eta'] ?? -1);
        $addedDate = (int) ($torrent['addedDate'] ?? 0);
        $doneDate = (int) ($torrent['doneDate'] ?? 0);
        $errorString = trim((string) ($torrent['errorString'] ?? ''));

        return new Torrent(
            id: (int) ($torrent['id'] ?? 0),
            hash: (string) ($torrent['hashString'] ?? ''),
            name: (string) ($torrent['name'] ?? ''),
            status: TorrentStatus::fromRpc((int) ($torrent['status'] ?? 0)),
            percentDone: round((float) ($torrent['percentDone'] ?? 0) * 100, 2),
            recheckPercent: round((float) ($torrent['recheckProgress'] ?? 0) * 100, 2),
            metadataPercent: round((float) ($torrent['metadataPercentComplete'] ?? 1) * 100, 2),
            rateDownload: (int) ($torrent['rateDownload'] ?? 0),
            rateUpload: (int) ($torrent['rateUpload'] ?? 0),
            etaSeconds: $eta >= 0 ? $eta : null,
            ratio: round((float) ($torrent['uploadRatio'] ?? 0), 3),
            totalSizeBytes: (int) ($torrent['totalSize'] ?? 0),
            sizeWhenDoneBytes: (int) ($torrent['sizeWhenDone'] ?? 0),
            leftUntilDoneBytes: (int) ($torrent['leftUntilDone'] ?? 0),
            downloadedBytes: (int) ($torrent['downloadedEver'] ?? 0),
            uploadedBytes: (int) ($torrent['uploadedEver'] ?? 0),
            peersConnected: (int) ($torrent['peersConnected'] ?? 0),
            peersSendingToUs: (int) ($torrent['peersSendingToUs'] ?? 0),
            peersGettingFromUs: (int) ($torrent['peersGettingFromUs'] ?? 0),
            queuePosition: (int) ($torrent['queuePosition'] ?? 0),
            isStalled: (bool) ($torrent['isStalled'] ?? false),
            isFinished: (bool) ($torrent['isFinished'] ?? false),
            downloadDir: (string) ($torrent['downloadDir'] ?? ''),
            error: (int) ($torrent['error'] ?? 0) !== 0 && $errorString !== '' ? $errorString : null,
            addedAt: $addedDate > 0 ? Carbon::createFromTimestamp($addedDate) : null,
            doneAt: $doneDate > 0 ? Carbon::createFromTimestamp($doneDate) : null,
            labels: array_values(array_map(strval(...), (array) ($torrent['labels'] ?? []))),
        );
    }

    /**
     * `files` carries the name and length, `fileStats` the progress and the
     * priority, and the two arrays share their indexes.
     *
     * @param  array<string, mixed>  $torrent
     * @return array<int, TorrentFile>
     */
    private function toFiles(array $torrent): array
    {
        $stats = is_array($torrent['fileStats'] ?? null) ? $torrent['fileStats'] : [];
        $files = [];

        foreach (is_array($torrent['files'] ?? null) ? $torrent['files'] : [] as $index => $file) {
            if (! is_array($file)) {
                continue;
            }

            $stat = is_array($stats[$index] ?? null) ? $stats[$index] : [];

            $files[] = new TorrentFile(
                index: (int) $index,
                name: (string) ($file['name'] ?? ''),
                lengthBytes: (int) ($file['length'] ?? 0),
                completedBytes: (int) ($stat['bytesCompleted'] ?? $file['bytesCompleted'] ?? 0),
                wanted: (bool) ($stat['wanted'] ?? true),
                priority: TorrentFilePriority::fromRpc((int) ($stat['priority'] ?? 0)),
            );
        }

        return $files;
    }

    /**
     * @param  array<string, mixed>  $torrent
     * @return array<int, TorrentTracker>
     */
    private function toTrackers(array $torrent): array
    {
        $trackers = [];

        foreach (is_array($torrent['trackerStats'] ?? null) ? $torrent['trackerStats'] : [] as $tracker) {
            if (! is_array($tracker)) {
                continue;
            }

            $trackers[] = new TorrentTracker(
                id: (int) ($tracker['id'] ?? 0),
                host: (string) ($tracker['host'] ?? ''),
                announce: (string) ($tracker['announce'] ?? ''),
                seederCount: max(0, (int) ($tracker['seederCount'] ?? 0)),
                leecherCount: max(0, (int) ($tracker['leecherCount'] ?? 0)),
                lastAnnounceResult: $this->nonEmpty($tracker['lastAnnounceResult'] ?? null),
                lastAnnounceSucceeded: (bool) ($tracker['lastAnnounceSucceeded'] ?? false),
            );
        }

        return $trackers;
    }

    /**
     * @param  array<string, mixed>  $torrent
     * @return array<int, TorrentPeer>
     */
    private function toPeers(array $torrent): array
    {
        $peers = [];

        foreach (is_array($torrent['peers'] ?? null) ? $torrent['peers'] : [] as $peer) {
            if (! is_array($peer)) {
                continue;
            }

            $peers[] = new TorrentPeer(
                address: (string) ($peer['address'] ?? ''),
                client: (string) ($peer['clientName'] ?? ''),
                progress: (float) ($peer['progress'] ?? 0),
                rateToClient: (int) ($peer['rateToClient'] ?? 0),
                rateToPeer: (int) ($peer['rateToPeer'] ?? 0),
                flags: (string) ($peer['flagStr'] ?? ''),
            );
        }

        return $peers;
    }

    /**
     * @param  array<string, mixed>  $torrent
     */
    private function toLimits(array $torrent): TorrentLimits
    {
        $seedRatioMode = (int) ($torrent['seedRatioMode'] ?? 0);

        return new TorrentLimits(
            downloadKilobytes: ($torrent['downloadLimited'] ?? false) ? (int) ($torrent['downloadLimit'] ?? 0) : null,
            uploadKilobytes: ($torrent['uploadLimited'] ?? false) ? (int) ($torrent['uploadLimit'] ?? 0) : null,
            seedRatio: $seedRatioMode === 1 ? round((float) ($torrent['seedRatioLimit'] ?? 0), 2) : null,
            seedForever: $seedRatioMode === 2,
            honorsSessionLimits: (bool) ($torrent['honorsSessionLimits'] ?? true),
        );
    }

    /**
     * @param  array<string, mixed>  $stats
     * @param  array<string, mixed>  $session
     */
    private function toStats(array $stats, array $session): SessionStats
    {
        $current = is_array($stats['current-stats'] ?? null) ? $stats['current-stats'] : [];
        $cumulative = is_array($stats['cumulative-stats'] ?? null) ? $stats['cumulative-stats'] : [];
        $freeSpace = $this->freeSpace((string) ($session['download-dir'] ?? ''));

        return new SessionStats(
            rateDownload: (int) ($stats['downloadSpeed'] ?? 0),
            rateUpload: (int) ($stats['uploadSpeed'] ?? 0),
            torrentCount: (int) ($stats['torrentCount'] ?? 0),
            activeTorrentCount: (int) ($stats['activeTorrentCount'] ?? 0),
            pausedTorrentCount: (int) ($stats['pausedTorrentCount'] ?? 0),
            sessionDownloadedBytes: (int) ($current['downloadedBytes'] ?? 0),
            sessionUploadedBytes: (int) ($current['uploadedBytes'] ?? 0),
            totalDownloadedBytes: (int) ($cumulative['downloadedBytes'] ?? 0),
            totalUploadedBytes: (int) ($cumulative['uploadedBytes'] ?? 0),
            freeSpaceBytes: $freeSpace['free'],
            totalSpaceBytes: $freeSpace['total'],
            version: (string) ($session['version'] ?? $session['rpc-version-semver'] ?? ''),
        );
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function toSettings(array $session): SessionSettings
    {
        return new SessionSettings(
            downloadDir: (string) ($session['download-dir'] ?? ''),
            startAddedTorrents: (bool) ($session['start-added-torrents'] ?? true),
            speedLimitDown: (int) ($session['speed-limit-down'] ?? 0),
            speedLimitDownEnabled: (bool) ($session['speed-limit-down-enabled'] ?? false),
            speedLimitUp: (int) ($session['speed-limit-up'] ?? 0),
            speedLimitUpEnabled: (bool) ($session['speed-limit-up-enabled'] ?? false),
            altSpeedDown: (int) ($session['alt-speed-down'] ?? 0),
            altSpeedUp: (int) ($session['alt-speed-up'] ?? 0),
            altSpeedEnabled: (bool) ($session['alt-speed-enabled'] ?? false),
            downloadQueueSize: (int) ($session['download-queue-size'] ?? 0),
            downloadQueueEnabled: (bool) ($session['download-queue-enabled'] ?? false),
            seedQueueSize: (int) ($session['seed-queue-size'] ?? 0),
            seedQueueEnabled: (bool) ($session['seed-queue-enabled'] ?? false),
            seedRatioLimit: round((float) ($session['seedRatioLimit'] ?? 0), 2),
            seedRatioLimited: (bool) ($session['seedRatioLimited'] ?? false),
            peerLimitGlobal: (int) ($session['peer-limit-global'] ?? 0),
            peerPort: (int) ($session['peer-port'] ?? 0),
        );
    }

    /**
     * Room left where the downloads land. A daemon that cannot stat the
     * directory should not take the whole page down with it, so this reports
     * zeroes rather than throwing.
     *
     * @return array{free: int, total: int}
     */
    private function freeSpace(string $path): array
    {
        if ($path === '') {
            return ['free' => 0, 'total' => 0];
        }

        try {
            $payload = $this->request('free-space', ['path' => $path]);
        } catch (TorrentException) {
            return ['free' => 0, 'total' => 0];
        }

        return [
            'free' => max(0, (int) ($payload['size-bytes'] ?? 0)),
            'total' => max(0, (int) ($payload['total_size'] ?? 0)),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function request(string $method, array $arguments = []): array
    {
        if ($this->url === '') {
            throw TorrentException::notConfigured();
        }

        $response = $this->send($method, $arguments);

        /**
         * The session id rotates whenever the daemon restarts, so a 409 is met
         * with the header it hands back and a single retry rather than an error.
         */
        if ($response->status() === 409) {
            $this->sessionId = $response->header('X-Transmission-Session-Id');

            $response = $this->send($method, $arguments);
        }

        if ($response->status() === 401) {
            throw TorrentException::unavailable('невірний логін або пароль');
        }

        if ($response->failed()) {
            throw TorrentException::unavailable("HTTP {$response->status()}");
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw TorrentException::unavailable('несподівана відповідь');
        }

        $result = (string) ($body['result'] ?? '');

        if ($result !== 'success') {
            throw TorrentException::rejected($result === '' ? 'невідома помилка' : $result);
        }

        return is_array($body['arguments'] ?? null) ? $body['arguments'] : [];
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function send(string $method, array $arguments): Response
    {
        $payload = ['method' => $method];

        if ($arguments !== []) {
            $payload['arguments'] = $arguments;
        }

        try {
            return Http::withBasicAuth($this->username, $this->password)
                ->withHeaders($this->sessionId === null ? [] : ['X-Transmission-Session-Id' => $this->sessionId])
                ->connectTimeout(min(5, $this->timeout))
                ->timeout($this->timeout)
                ->asJson()
                ->post($this->url, $payload);
        } catch (ConnectionException $exception) {
            throw TorrentException::unavailable($exception->getMessage());
        }
    }

    private function nonEmpty(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }
}

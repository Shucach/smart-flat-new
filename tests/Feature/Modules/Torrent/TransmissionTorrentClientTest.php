<?php

use App\Enums\TorrentAction;
use App\Enums\TorrentFilePriority;
use App\Enums\TorrentStatus;
use App\Modules\Torrent\Data\SessionSettings;
use App\Modules\Torrent\Data\TorrentLimits;
use App\Modules\Torrent\Exceptions\TorrentException;
use App\Modules\Torrent\Services\TransmissionTorrentClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const RPC_URL = 'http://transmission.test:9091/transmission/rpc';

beforeEach(function () {
    Http::preventStrayRequests();

    $this->client = new TransmissionTorrentClient(RPC_URL, 'smartflat', 'secret', 5);
});

/**
 * Answers each RPC method with the payload registered for it.
 *
 * @param  array<string, array<string, mixed>>  $byMethod
 */
function fakeRpc(array $byMethod): void
{
    Http::fake(function (Request $request) use ($byMethod) {
        $method = $request->data()['method'] ?? '';

        return Http::response([
            'result' => 'success',
            'arguments' => $byMethod[$method] ?? [],
        ]);
    });
}

/**
 * @return array<string, mixed>
 */
function sessionPayload(): array
{
    return [
        'download-dir' => '/downloads',
        'start-added-torrents' => true,
        'speed-limit-down' => 100,
        'speed-limit-down-enabled' => false,
        'speed-limit-up' => 80,
        'speed-limit-up-enabled' => true,
        'alt-speed-down' => 50,
        'alt-speed-up' => 40,
        'alt-speed-enabled' => false,
        'download-queue-size' => 5,
        'download-queue-enabled' => true,
        'seed-queue-size' => 10,
        'seed-queue-enabled' => false,
        'seedRatioLimit' => 3,
        'seedRatioLimited' => true,
        'peer-limit-global' => 200,
        'peer-port' => 51413,
        'version' => '4.0.6',
    ];
}

it('maps the torrent list, the counters and the settings of a session', function () {
    fakeRpc([
        'torrent-get' => ['torrents' => [[
            'id' => 3,
            'hashString' => 'abc',
            'name' => 'Ubuntu',
            'status' => 4,
            'percentDone' => 0.4256,
            'eta' => 900,
            'uploadRatio' => 1.2345,
            'totalSize' => 4000,
            'error' => 0,
            'errorString' => '',
            'addedDate' => 1_700_000_000,
            'doneDate' => 0,
            'labels' => ['linux'],
        ]]],
        'session-get' => sessionPayload(),
        'session-stats' => [
            'downloadSpeed' => 2048,
            'uploadSpeed' => 1024,
            'torrentCount' => 1,
            'activeTorrentCount' => 1,
            'pausedTorrentCount' => 0,
            'current-stats' => ['downloadedBytes' => 10, 'uploadedBytes' => 20],
            'cumulative-stats' => ['downloadedBytes' => 30, 'uploadedBytes' => 40],
        ],
        'free-space' => ['size-bytes' => 900, 'total_size' => 1000],
    ]);

    $overview = $this->client->overview();
    $torrent = $overview->torrents[0];

    expect($torrent->id)->toBe(3)
        ->and($torrent->status)->toBe(TorrentStatus::Downloading)
        ->and($torrent->percentDone)->toBe(42.56)
        ->and($torrent->etaSeconds)->toBe(900)
        ->and($torrent->ratio)->toBe(1.235)
        ->and($torrent->error)->toBeNull()
        ->and($torrent->labels)->toBe(['linux'])
        ->and($torrent->addedAt?->timestamp)->toBe(1_700_000_000)
        ->and($torrent->doneAt)->toBeNull()
        ->and($overview->stats->rateDownload)->toBe(2048)
        ->and($overview->stats->freeSpaceBytes)->toBe(900)
        ->and($overview->stats->totalSpaceBytes)->toBe(1000)
        ->and($overview->stats->version)->toBe('4.0.6')
        ->and($overview->settings->speedLimitUpEnabled)->toBeTrue()
        ->and($overview->settings->downloadDir)->toBe('/downloads');

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization')
        && $request->data()['method'] === 'free-space'
        && $request->data()['arguments']['path'] === '/downloads');
});

it('reports an eta the daemon cannot work out yet as unknown', function () {
    fakeRpc([
        'torrent-get' => ['torrents' => [['id' => 1, 'eta' => -1, 'status' => 4]]],
        'session-get' => sessionPayload(),
        'session-stats' => [],
        'free-space' => [],
    ]);

    expect($this->client->overview()->torrents[0]->etaSeconds)->toBeNull();
});

it('keeps the error string of a failing torrent', function () {
    fakeRpc([
        'torrent-get' => ['torrents' => [[
            'id' => 1,
            'status' => 0,
            'error' => 3,
            'errorString' => 'Unregistered torrent',
        ]]],
        'session-get' => sessionPayload(),
        'session-stats' => [],
        'free-space' => [],
    ]);

    expect($this->client->overview()->torrents[0]->error)->toBe('Unregistered torrent');
});

it('joins the file list with the per file statistics', function () {
    fakeRpc(['torrent-get' => ['torrents' => [[
        'id' => 3,
        'status' => 4,
        'files' => [
            ['name' => 'a.mkv', 'length' => 100],
            ['name' => 'b.nfo', 'length' => 10],
        ],
        'fileStats' => [
            ['bytesCompleted' => 50, 'wanted' => true, 'priority' => 1],
            ['bytesCompleted' => 0, 'wanted' => false, 'priority' => -1],
        ],
        'trackerStats' => [[
            'id' => 1,
            'host' => 'tracker.test:80',
            'announce' => 'http://tracker.test/announce',
            'seederCount' => 9,
            'leecherCount' => 2,
            'lastAnnounceResult' => 'Success',
            'lastAnnounceSucceeded' => true,
        ]],
        'peers' => [[
            'address' => '10.0.0.1',
            'clientName' => 'Transmission 4.0.6',
            'progress' => 0.5,
            'rateToClient' => 100,
            'rateToPeer' => 20,
            'flagStr' => 'UE',
        ]],
        'seedRatioMode' => 1,
        'seedRatioLimit' => 2.5,
        'downloadLimited' => true,
        'downloadLimit' => 300,
        'magnetLink' => 'magnet:?xt=urn:btih:abc',
        'pieceCount' => 12,
        'pieceSize' => 1024,
    ]]]]);

    $detail = $this->client->detail(3);

    expect($detail->files)->toHaveCount(2)
        ->and($detail->files[0]->priority)->toBe(TorrentFilePriority::High)
        ->and($detail->files[0]->percentDone())->toBe(50.0)
        ->and($detail->files[1]->wanted)->toBeFalse()
        ->and($detail->files[1]->priority)->toBe(TorrentFilePriority::Low)
        ->and($detail->trackers[0]->host)->toBe('tracker.test:80')
        ->and($detail->peers[0]->toArray()['progressPercent'])->toBe(50.0)
        ->and($detail->limits->seedRatio)->toBe(2.5)
        ->and($detail->limits->seedForever)->toBeFalse()
        ->and($detail->limits->downloadKilobytes)->toBe(300)
        ->and($detail->limits->uploadKilobytes)->toBeNull()
        ->and($detail->pieceCount)->toBe(12);
});

it('fails when the daemon knows nothing of the torrent', function () {
    fakeRpc(['torrent-get' => ['torrents' => []]]);

    expect(fn () => $this->client->detail(404))->toThrow(TorrentException::class, 'Торент [404] не знайдено.');
});

it('sends a magnet link as the filename of a new torrent', function () {
    fakeRpc(['torrent-add' => ['torrent-added' => ['id' => 9, 'name' => 'Ubuntu']]]);

    expect($this->client->addUrl('magnet:?xt=urn:btih:abc', '/downloads/films', true))->toBe('Ubuntu');

    Http::assertSent(fn (Request $request) => $request->data()['arguments'] === [
        'filename' => 'magnet:?xt=urn:btih:abc',
        'paused' => true,
        'download-dir' => '/downloads/films',
    ]);
});

it('sends an uploaded file as base64 metainfo', function () {
    fakeRpc(['torrent-add' => ['torrent-added' => ['name' => 'Ubuntu']]]);

    $this->client->addMetainfo('d8:announce');

    Http::assertSent(fn (Request $request) => $request->data()['arguments'] === [
        'metainfo' => base64_encode('d8:announce'),
        'paused' => false,
    ]);
});

it('says so when the torrent is already there', function () {
    fakeRpc(['torrent-add' => ['torrent-duplicate' => ['id' => 9, 'name' => 'Ubuntu']]]);

    expect(fn () => $this->client->addUrl('magnet:?xt=urn:btih:abc'))
        ->toThrow(TorrentException::class, 'торент «Ubuntu» вже додано');
});

it('removes torrents with their data', function () {
    fakeRpc(['torrent-remove' => []]);

    $this->client->remove([3, 4], true);

    Http::assertSent(fn (Request $request) => $request->data() === [
        'method' => 'torrent-remove',
        'arguments' => ['ids' => [3, 4], 'delete-local-data' => true],
    ]);
});

it('maps each action onto the rpc method that performs it', function (TorrentAction $action, string $method) {
    fakeRpc([$method => []]);

    $this->client->act($action, [3]);

    Http::assertSent(fn (Request $request) => $request->data()['method'] === $method
        && $request->data()['arguments'] === ['ids' => [3]]);
})->with([
    'start' => [TorrentAction::Start, 'torrent-start'],
    'start now' => [TorrentAction::StartNow, 'torrent-start-now'],
    'stop' => [TorrentAction::Stop, 'torrent-stop'],
    'verify' => [TorrentAction::Verify, 'torrent-verify'],
    'reannounce' => [TorrentAction::Reannounce, 'torrent-reannounce'],
    'queue top' => [TorrentAction::QueueTop, 'queue-move-top'],
    'queue bottom' => [TorrentAction::QueueBottom, 'queue-move-bottom'],
]);

it('excludes files under the unwanted key', function () {
    fakeRpc(['torrent-set' => []]);

    $this->client->setFilesWanted(3, [1, 2], false);

    Http::assertSent(fn (Request $request) => $request->data()['arguments'] === [
        'ids' => [3],
        'files-unwanted' => [1, 2],
    ]);
});

it('moves files into the priority bucket that names them', function () {
    fakeRpc(['torrent-set' => []]);

    $this->client->setFilePriority(3, [0], TorrentFilePriority::High);

    Http::assertSent(fn (Request $request) => $request->data()['arguments'] === [
        'ids' => [3],
        'priority-high' => [0],
    ]);
});

it('turns a missing speed limit into the disabled flag', function () {
    fakeRpc(['torrent-set' => []]);

    $this->client->setLimits(3, new TorrentLimits(500, null, null, seedForever: true));

    Http::assertSent(function (Request $request) {
        $arguments = $request->data()['arguments'];

        return $arguments['downloadLimited'] === true
            && $arguments['downloadLimit'] === 500
            && $arguments['uploadLimited'] === false
            && $arguments['seedRatioMode'] === 2;
    });
});

it('writes the session settings under the keys the daemon expects', function () {
    fakeRpc(['session-set' => []]);

    $this->client->updateSession(new SessionSettings(
        downloadDir: '',
        startAddedTorrents: false,
        speedLimitDown: 800,
        speedLimitDownEnabled: true,
        speedLimitUp: 100,
        speedLimitUpEnabled: false,
        altSpeedDown: 50,
        altSpeedUp: 40,
        altSpeedEnabled: true,
        downloadQueueSize: 3,
        downloadQueueEnabled: true,
        seedQueueSize: 10,
        seedQueueEnabled: false,
        seedRatioLimit: 2.0,
        seedRatioLimited: true,
        peerLimitGlobal: 200,
        peerPort: 0,
    ));

    Http::assertSent(function (Request $request) {
        $arguments = $request->data()['arguments'];

        return $arguments['speed-limit-down'] === 800
            && $arguments['alt-speed-enabled'] === true
            && $arguments['seedRatioLimit'] === 2.0
            && ! array_key_exists('download-dir', $arguments)
            && ! array_key_exists('peer-port', $arguments);
    });
});

it('repeats a request that was answered with a fresh session id', function () {
    Http::fake([RPC_URL => Http::sequence()
        ->push('conflict', 409, ['X-Transmission-Session-Id' => 'session-token'])
        ->push(['result' => 'success', 'arguments' => ['port-is-open' => true]]),
    ]);

    expect($this->client->testPort())->toBeTrue();

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request->hasHeader('X-Transmission-Session-Id', 'session-token'));
});

it('names bad credentials rather than reporting a bare http status', function () {
    Http::fake([RPC_URL => Http::response('', 401)]);

    expect(fn () => $this->client->testPort())
        ->toThrow(TorrentException::class, 'невірний логін або пароль');
});

it('passes back the reason the daemon gives for refusing', function () {
    Http::fake([RPC_URL => Http::response(['result' => 'invalid argument'])]);

    expect(fn () => $this->client->testPort())
        ->toThrow(TorrentException::class, 'invalid argument');
});

it('refuses to reach out at all when no endpoint is configured', function () {
    Http::fake();

    expect(fn () => (new TransmissionTorrentClient('', '', ''))->testPort())
        ->toThrow(TorrentException::class, 'Торент-клієнт не налаштовано.');

    Http::assertNothingSent();
});

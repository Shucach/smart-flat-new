<?php

use App\Enums\Permission;
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
use App\Modules\Torrent\Data\TorrentTracker;
use App\Modules\Torrent\Exceptions\TorrentException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\mock;

function torrentFixture(int $id = 7, TorrentStatus $status = TorrentStatus::Downloading): Torrent
{
    return new Torrent(
        id: $id,
        hash: 'abc123',
        name: 'Ubuntu 24.04',
        status: $status,
        percentDone: 42.5,
        recheckPercent: 0.0,
        metadataPercent: 100.0,
        rateDownload: 1024,
        rateUpload: 512,
        etaSeconds: 600,
        ratio: 1.25,
        totalSizeBytes: 4_000_000,
        sizeWhenDoneBytes: 4_000_000,
        leftUntilDoneBytes: 2_300_000,
        downloadedBytes: 1_700_000,
        uploadedBytes: 2_125_000,
        peersConnected: 5,
        peersSendingToUs: 3,
        peersGettingFromUs: 1,
        queuePosition: 0,
        isStalled: false,
        isFinished: false,
        downloadDir: '/downloads',
        error: null,
        addedAt: Carbon::parse('2026-01-02 03:04:05'),
        doneAt: null,
    );
}

function overviewFixture(): TorrentOverview
{
    return new TorrentOverview(
        torrents: [torrentFixture()],
        stats: new SessionStats(1024, 512, 1, 1, 0, 10, 20, 30, 40, 900, 1000, '4.0.6'),
        settings: new SessionSettings(
            downloadDir: '/downloads',
            startAddedTorrents: true,
            speedLimitDown: 100,
            speedLimitDownEnabled: false,
            speedLimitUp: 100,
            speedLimitUpEnabled: false,
            altSpeedDown: 50,
            altSpeedUp: 50,
            altSpeedEnabled: false,
            downloadQueueSize: 5,
            downloadQueueEnabled: true,
            seedQueueSize: 10,
            seedQueueEnabled: false,
            seedRatioLimit: 3.0,
            seedRatioLimited: true,
            peerLimitGlobal: 200,
            peerPort: 51413,
        ),
    );
}

describe('index', function () {
    it('renders the torrents, the counters and the session settings', function () {
        mock(TorrentClient::class)->shouldReceive('overview')->once()->andReturn(overviewFixture());

        $this->actingAs(userWithPermissions(Permission::TorrentView))
            ->get(route('torrents.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('torrents/Index')
                ->where('error', null)
                ->where('torrent', null)
                ->has('torrents', 1, fn (AssertableInertia $torrent) => $torrent
                    ->where('id', 7)
                    ->where('name', 'Ubuntu 24.04')
                    ->where('status', 'downloading')
                    ->where('statusLabel', 'Завантаження')
                    ->where('percentDone', 42.5)
                    ->where('isPaused', false)
                    ->etc())
                ->where('stats.rateDownload', 1024)
                ->where('settings.downloadDir', '/downloads')
                ->where('limits.maxFileMegabytes', 5));
    });

    it('loads the detail of the selected torrent', function () {
        $client = mock(TorrentClient::class);
        $client->shouldReceive('overview')->andReturn(overviewFixture());
        $client->shouldReceive('detail')->once()->with(7)->andReturn(new TorrentDetail(
            torrent: torrentFixture(),
            files: [new TorrentFile(0, 'ubuntu.iso', 4_000_000, 1_700_000, true, TorrentFilePriority::High)],
            trackers: [new TorrentTracker(1, 'tracker.test:80', 'http://tracker.test/announce', 9, 2, 'Success', true)],
            peers: [],
            limits: new TorrentLimits(null, null, null),
            comment: 'released',
            creator: 'transmission',
            magnetLink: 'magnet:?xt=urn:btih:abc123',
            lastActivityAt: null,
            pieceCount: 100,
            pieceSizeBytes: 40_000,
        ));

        $this->actingAs(userWithPermissions(Permission::TorrentView))
            ->get(route('torrents.index', ['selected' => 7]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('torrent.id', 7)
                ->has('torrent.files', 1)
                ->has('torrent.trackers', 1)
                ->where('torrent.files.0.priority', 'high')
                ->where('torrent.limits.honorsSessionLimits', true)
                ->etc());
    });

    it('reports an unreachable daemon instead of an empty list', function () {
        mock(TorrentClient::class)
            ->shouldReceive('overview')
            ->andThrow(TorrentException::unavailable('відмова у зʼєднанні'));

        $this->actingAs(userWithPermissions(Permission::TorrentView))
            ->get(route('torrents.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('torrents', [])
                ->where('stats', null)
                ->where('settings', null)
                ->where('error', 'Торент-клієнт недоступний: відмова у зʼєднанні')
                ->etc());
    });

    it('returns 403 for a user without the torrent.view permission', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView))
            ->get(route('torrents.index'))
            ->assertForbidden();
    });
});

describe('store', function () {
    it('adds a torrent from a magnet link', function () {
        mock(TorrentClient::class)
            ->shouldReceive('addUrl')->once()
            ->with('magnet:?xt=urn:btih:abc123', '/downloads/films', true)
            ->andReturn('Ubuntu 24.04');

        $this->actingAs(userWithPermissions(Permission::TorrentAdd))
            ->from(route('torrents.index'))
            ->post(route('torrents.store'), [
                'url' => 'magnet:?xt=urn:btih:abc123',
                'downloadDir' => '/downloads/films',
                'paused' => true,
            ])
            ->assertRedirect(route('torrents.index'))
            ->assertSessionHasNoErrors();
    });

    it('adds a torrent from an uploaded metainfo file', function () {
        mock(TorrentClient::class)
            ->shouldReceive('addMetainfo')->once()
            ->withArgs(fn (string $contents, ?string $dir, bool $paused): bool => $contents === 'd8:announce'
                && $dir === null
                && $paused === false)
            ->andReturn('Ubuntu 24.04');

        $this->actingAs(userWithPermissions(Permission::TorrentAdd))
            ->from(route('torrents.index'))
            ->post(route('torrents.store'), [
                'file' => UploadedFile::fake()->createWithContent('ubuntu.torrent', 'd8:announce'),
            ])
            ->assertRedirect(route('torrents.index'))
            ->assertSessionHasNoErrors();
    });

    it('rejects a request with neither a link nor a file', function () {
        mock(TorrentClient::class)->shouldNotReceive('addUrl');

        $this->actingAs(userWithPermissions(Permission::TorrentAdd))
            ->from(route('torrents.index'))
            ->post(route('torrents.store'), [])
            ->assertSessionHasErrors('url');
    });

    it('rejects a link that is neither a magnet nor an http url', function () {
        mock(TorrentClient::class)->shouldNotReceive('addUrl');

        $this->actingAs(userWithPermissions(Permission::TorrentAdd))
            ->from(route('torrents.index'))
            ->post(route('torrents.store'), ['url' => 'file:///etc/passwd'])
            ->assertSessionHasErrors('url');
    });

    it('hands the refusal of the daemon back to the form', function () {
        mock(TorrentClient::class)
            ->shouldReceive('addUrl')
            ->andThrow(TorrentException::rejected('торент «Ubuntu» вже додано'));

        $this->actingAs(userWithPermissions(Permission::TorrentAdd))
            ->from(route('torrents.index'))
            ->post(route('torrents.store'), ['url' => 'magnet:?xt=urn:btih:abc123'])
            ->assertSessionHasErrors('url');
    });

    it('returns 403 for a user without the torrent.add permission', function () {
        mock(TorrentClient::class)->shouldNotReceive('addUrl');

        $this->actingAs(userWithPermissions(Permission::TorrentView))
            ->post(route('torrents.store'), ['url' => 'magnet:?xt=urn:btih:abc123'])
            ->assertForbidden();
    });
});

describe('destroy', function () {
    it('removes torrents and keeps the files by default', function () {
        mock(TorrentClient::class)->shouldReceive('remove')->once()->with([7, 8], false);

        $this->actingAs(userWithPermissions(Permission::TorrentDelete))
            ->from(route('torrents.index'))
            ->delete(route('torrents.destroy'), ['ids' => [7, 8]])
            ->assertRedirect(route('torrents.index'))
            ->assertSessionHasNoErrors();
    });

    it('deletes the downloaded data when asked to', function () {
        mock(TorrentClient::class)->shouldReceive('remove')->once()->with([7], true);

        $this->actingAs(userWithPermissions(Permission::TorrentDelete))
            ->from(route('torrents.index'))
            ->delete(route('torrents.destroy'), ['ids' => [7], 'deleteData' => true])
            ->assertSessionHasNoErrors();
    });

    it('rejects a request without ids', function () {
        mock(TorrentClient::class)->shouldNotReceive('remove');

        $this->actingAs(userWithPermissions(Permission::TorrentDelete))
            ->from(route('torrents.index'))
            ->delete(route('torrents.destroy'), [])
            ->assertSessionHasErrors('ids');
    });

    it('returns 403 for a user without the torrent.delete permission', function () {
        mock(TorrentClient::class)->shouldNotReceive('remove');

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->delete(route('torrents.destroy'), ['ids' => [7]])
            ->assertForbidden();
    });
});

describe('action', function () {
    it('performs the requested action on the given torrents', function (TorrentAction $action) {
        mock(TorrentClient::class)->shouldReceive('act')->once()->with($action, [7]);

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->from(route('torrents.index'))
            ->post(route('torrents.action'), ['action' => $action->value, 'ids' => [7]])
            ->assertRedirect(route('torrents.index'))
            ->assertSessionHasNoErrors();
    })->with([
        'start' => TorrentAction::Start,
        'stop' => TorrentAction::Stop,
        'verify' => TorrentAction::Verify,
        'queue-top' => TorrentAction::QueueTop,
    ]);

    it('rejects an unknown action', function () {
        mock(TorrentClient::class)->shouldNotReceive('act');

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->from(route('torrents.index'))
            ->post(route('torrents.action'), ['action' => 'explode', 'ids' => [7]])
            ->assertSessionHasErrors('action');
    });

    it('returns 403 for a user without the torrent.manage permission', function () {
        mock(TorrentClient::class)->shouldNotReceive('act');

        $this->actingAs(userWithPermissions(Permission::TorrentView))
            ->post(route('torrents.action'), ['action' => 'start', 'ids' => [7]])
            ->assertForbidden();
    });
});

describe('files', function () {
    it('excludes files from a torrent', function () {
        mock(TorrentClient::class)->shouldReceive('setFilesWanted')->once()->with(7, [1, 2], false);

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->from(route('torrents.index'))
            ->put(route('torrents.files.update', ['torrent' => 7]), [
                'indexes' => [1, 2],
                'wanted' => false,
            ])
            ->assertSessionHasNoErrors();
    });

    it('changes the priority of files', function () {
        mock(TorrentClient::class)
            ->shouldReceive('setFilePriority')->once()
            ->with(7, [0], TorrentFilePriority::High);

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->from(route('torrents.index'))
            ->put(route('torrents.files.update', ['torrent' => 7]), [
                'indexes' => [0],
                'priority' => 'high',
            ])
            ->assertSessionHasNoErrors();
    });

    it('rejects a request that changes nothing', function () {
        mock(TorrentClient::class)->shouldNotReceive('setFilesWanted');

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->from(route('torrents.index'))
            ->put(route('torrents.files.update', ['torrent' => 7]), ['indexes' => [0]])
            ->assertSessionHasErrors('wanted');
    });
});

describe('limits', function () {
    it('stores the per torrent limits', function () {
        mock(TorrentClient::class)
            ->shouldReceive('setLimits')->once()
            ->withArgs(function (int $id, TorrentLimits $limits): bool {
                return $id === 7
                    && $limits->downloadKilobytes === 500
                    && $limits->uploadKilobytes === null
                    && $limits->seedRatio === 2.5
                    && $limits->seedForever === false;
            });

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->from(route('torrents.index'))
            ->put(route('torrents.limits.update', ['torrent' => 7]), [
                'downloadKilobytes' => 500,
                'uploadKilobytes' => null,
                'seedRatio' => 2.5,
                'honorsSessionLimits' => true,
            ])
            ->assertSessionHasNoErrors();
    });

    it('drops the ratio when the torrent seeds forever', function () {
        mock(TorrentClient::class)
            ->shouldReceive('setLimits')->once()
            ->withArgs(fn (int $id, TorrentLimits $limits): bool => $limits->seedForever
                && $limits->seedRatio === null);

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->from(route('torrents.index'))
            ->put(route('torrents.limits.update', ['torrent' => 7]), [
                'seedRatio' => 2.5,
                'seedForever' => true,
            ])
            ->assertSessionHasNoErrors();
    });
});

describe('settings', function () {
    it('writes the session settings back to the daemon', function () {
        mock(TorrentClient::class)
            ->shouldReceive('updateSession')->once()
            ->withArgs(function (SessionSettings $settings): bool {
                return $settings->speedLimitDown === 800
                    && $settings->speedLimitDownEnabled
                    && $settings->altSpeedEnabled === false
                    && $settings->seedRatioLimit === 2.0;
            });

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->from(route('torrents.index'))
            ->put(route('torrents.settings.update'), [
                'startAddedTorrents' => true,
                'speedLimitDown' => 800,
                'speedLimitDownEnabled' => true,
                'speedLimitUp' => 100,
                'speedLimitUpEnabled' => false,
                'altSpeedDown' => 50,
                'altSpeedUp' => 50,
                'altSpeedEnabled' => false,
                'downloadQueueSize' => 5,
                'downloadQueueEnabled' => true,
                'seedQueueSize' => 10,
                'seedQueueEnabled' => false,
                'seedRatioLimit' => 2,
                'seedRatioLimited' => true,
                'peerLimitGlobal' => 200,
            ])
            ->assertSessionHasNoErrors();
    });

    it('reports the state of the listening port', function () {
        mock(TorrentClient::class)->shouldReceive('testPort')->once()->andReturn(true);

        $this->actingAs(userWithPermissions(Permission::TorrentManage))
            ->from(route('torrents.index'))
            ->post(route('torrents.port-test'))
            ->assertRedirect(route('torrents.index'));
    });

    it('returns 403 for a user without the torrent.manage permission', function () {
        mock(TorrentClient::class)->shouldNotReceive('updateSession');

        $this->actingAs(userWithPermissions(Permission::TorrentView))
            ->put(route('torrents.settings.update'), [])
            ->assertForbidden();
    });
});

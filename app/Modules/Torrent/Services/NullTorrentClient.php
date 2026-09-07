<?php

namespace App\Modules\Torrent\Services;

use App\Enums\TorrentAction;
use App\Enums\TorrentFilePriority;
use App\Modules\Torrent\Contracts\TorrentClient;
use App\Modules\Torrent\Data\SessionSettings;
use App\Modules\Torrent\Data\TorrentDetail;
use App\Modules\Torrent\Data\TorrentLimits;
use App\Modules\Torrent\Data\TorrentOverview;
use App\Modules\Torrent\Exceptions\TorrentException;

/**
 * Stands in where no torrent daemon is configured. It refuses everything rather
 * than answering quietly, so a panel that cannot reach the daemon says so
 * instead of showing an empty list that looks like "no torrents".
 */
final class NullTorrentClient implements TorrentClient
{
    public function overview(): TorrentOverview
    {
        throw TorrentException::notConfigured();
    }

    public function detail(int $id): TorrentDetail
    {
        throw TorrentException::notConfigured();
    }

    public function addUrl(string $url, ?string $downloadDir = null, bool $paused = false): string
    {
        throw TorrentException::notConfigured();
    }

    public function addMetainfo(string $contents, ?string $downloadDir = null, bool $paused = false): string
    {
        throw TorrentException::notConfigured();
    }

    public function remove(array $ids, bool $deleteData): void
    {
        throw TorrentException::notConfigured();
    }

    public function act(TorrentAction $action, array $ids): void
    {
        throw TorrentException::notConfigured();
    }

    public function setFilesWanted(int $id, array $indexes, bool $wanted): void
    {
        throw TorrentException::notConfigured();
    }

    public function setFilePriority(int $id, array $indexes, TorrentFilePriority $priority): void
    {
        throw TorrentException::notConfigured();
    }

    public function setLimits(int $id, TorrentLimits $limits): void
    {
        throw TorrentException::notConfigured();
    }

    public function updateSession(SessionSettings $settings): void
    {
        throw TorrentException::notConfigured();
    }

    public function testPort(): bool
    {
        throw TorrentException::notConfigured();
    }
}

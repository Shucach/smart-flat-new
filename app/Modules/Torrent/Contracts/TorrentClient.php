<?php

namespace App\Modules\Torrent\Contracts;

use App\Enums\TorrentAction;
use App\Enums\TorrentFilePriority;
use App\Modules\Torrent\Data\SessionSettings;
use App\Modules\Torrent\Data\TorrentDetail;
use App\Modules\Torrent\Data\TorrentLimits;
use App\Modules\Torrent\Data\TorrentOverview;
use App\Modules\Torrent\Exceptions\TorrentException;

interface TorrentClient
{
    /**
     * Every torrent the daemon holds, with the session counters and settings.
     *
     * @throws TorrentException
     */
    public function overview(): TorrentOverview;

    /**
     * One torrent with its files, trackers and peers.
     *
     * @throws TorrentException
     */
    public function detail(int $id): TorrentDetail;

    /**
     * Add a torrent from a magnet link or the URL of a .torrent file.
     *
     * @return string the name the daemon gave the torrent
     *
     * @throws TorrentException
     */
    public function addUrl(string $url, ?string $downloadDir = null, bool $paused = false): string;

    /**
     * Add a torrent from the raw contents of a .torrent file.
     *
     * @throws TorrentException
     */
    public function addMetainfo(string $contents, ?string $downloadDir = null, bool $paused = false): string;

    /**
     * @param  array<int, int>  $ids
     *
     * @throws TorrentException
     */
    public function remove(array $ids, bool $deleteData): void;

    /**
     * @param  array<int, int>  $ids
     *
     * @throws TorrentException
     */
    public function act(TorrentAction $action, array $ids): void;

    /**
     * Include or exclude files of a torrent by their index.
     *
     * @param  array<int, int>  $indexes
     *
     * @throws TorrentException
     */
    public function setFilesWanted(int $id, array $indexes, bool $wanted): void;

    /**
     * @param  array<int, int>  $indexes
     *
     * @throws TorrentException
     */
    public function setFilePriority(int $id, array $indexes, TorrentFilePriority $priority): void;

    /**
     * @throws TorrentException
     */
    public function setLimits(int $id, TorrentLimits $limits): void;

    /**
     * @throws TorrentException
     */
    public function updateSession(SessionSettings $settings): void;

    /**
     * Ask the daemon whether its listening port is reachable from outside.
     *
     * @throws TorrentException
     */
    public function testPort(): bool;
}

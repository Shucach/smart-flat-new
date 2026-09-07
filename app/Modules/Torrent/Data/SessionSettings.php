<?php

namespace App\Modules\Torrent\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * The session-wide settings the panel both shows and writes back.
 *
 * Speeds are kilobytes per second, which is the unit `session-get` uses; the
 * alternative ("turtle") pair is the one the speed switch toggles between.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class SessionSettings implements Arrayable
{
    public function __construct(
        public string $downloadDir,
        public bool $startAddedTorrents,
        public int $speedLimitDown,
        public bool $speedLimitDownEnabled,
        public int $speedLimitUp,
        public bool $speedLimitUpEnabled,
        public int $altSpeedDown,
        public int $altSpeedUp,
        public bool $altSpeedEnabled,
        public int $downloadQueueSize,
        public bool $downloadQueueEnabled,
        public int $seedQueueSize,
        public bool $seedQueueEnabled,
        public float $seedRatioLimit,
        public bool $seedRatioLimited,
        public int $peerLimitGlobal,
        public int $peerPort,
    ) {}

    /**
     * The keys `session-set` accepts, mapped from the properties above.
     *
     * @return array<string, mixed>
     */
    public function toRpcArguments(): array
    {
        return [
            'start-added-torrents' => $this->startAddedTorrents,
            'speed-limit-down' => $this->speedLimitDown,
            'speed-limit-down-enabled' => $this->speedLimitDownEnabled,
            'speed-limit-up' => $this->speedLimitUp,
            'speed-limit-up-enabled' => $this->speedLimitUpEnabled,
            'alt-speed-down' => $this->altSpeedDown,
            'alt-speed-up' => $this->altSpeedUp,
            'alt-speed-enabled' => $this->altSpeedEnabled,
            'download-queue-size' => $this->downloadQueueSize,
            'download-queue-enabled' => $this->downloadQueueEnabled,
            'seed-queue-size' => $this->seedQueueSize,
            'seed-queue-enabled' => $this->seedQueueEnabled,
            'seedRatioLimit' => $this->seedRatioLimit,
            'seedRatioLimited' => $this->seedRatioLimited,
            'peer-limit-global' => $this->peerLimitGlobal,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'downloadDir' => $this->downloadDir,
            'startAddedTorrents' => $this->startAddedTorrents,
            'speedLimitDown' => $this->speedLimitDown,
            'speedLimitDownEnabled' => $this->speedLimitDownEnabled,
            'speedLimitUp' => $this->speedLimitUp,
            'speedLimitUpEnabled' => $this->speedLimitUpEnabled,
            'altSpeedDown' => $this->altSpeedDown,
            'altSpeedUp' => $this->altSpeedUp,
            'altSpeedEnabled' => $this->altSpeedEnabled,
            'downloadQueueSize' => $this->downloadQueueSize,
            'downloadQueueEnabled' => $this->downloadQueueEnabled,
            'seedQueueSize' => $this->seedQueueSize,
            'seedQueueEnabled' => $this->seedQueueEnabled,
            'seedRatioLimit' => $this->seedRatioLimit,
            'seedRatioLimited' => $this->seedRatioLimited,
            'peerLimitGlobal' => $this->peerLimitGlobal,
            'peerPort' => $this->peerPort,
        ];
    }
}

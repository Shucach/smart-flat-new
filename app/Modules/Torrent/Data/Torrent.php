<?php

namespace App\Modules\Torrent\Data;

use App\Enums\TorrentStatus;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

/**
 * One torrent as the list needs it: enough to draw a row and decide which
 * buttons that row offers, and nothing that costs an extra RPC field per file.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Torrent implements Arrayable
{
    /**
     * @param  array<int, string>  $labels
     */
    public function __construct(
        public int $id,
        public string $hash,
        public string $name,
        public TorrentStatus $status,
        public float $percentDone,
        public float $recheckPercent,
        public float $metadataPercent,
        public int $rateDownload,
        public int $rateUpload,
        public ?int $etaSeconds,
        public float $ratio,
        public int $totalSizeBytes,
        public int $sizeWhenDoneBytes,
        public int $leftUntilDoneBytes,
        public int $downloadedBytes,
        public int $uploadedBytes,
        public int $peersConnected,
        public int $peersSendingToUs,
        public int $peersGettingFromUs,
        public int $queuePosition,
        public bool $isStalled,
        public bool $isFinished,
        public string $downloadDir,
        public ?string $error,
        public ?Carbon $addedAt,
        public ?Carbon $doneAt,
        public array $labels = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hash' => $this->hash,
            'name' => $this->name,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'percentDone' => $this->percentDone,
            'recheckPercent' => $this->recheckPercent,
            'metadataPercent' => $this->metadataPercent,
            'rateDownload' => $this->rateDownload,
            'rateUpload' => $this->rateUpload,
            'etaSeconds' => $this->etaSeconds,
            'ratio' => $this->ratio,
            'totalSizeBytes' => $this->totalSizeBytes,
            'sizeForHumans' => Number::fileSize($this->totalSizeBytes, maxPrecision: 1),
            'sizeWhenDoneBytes' => $this->sizeWhenDoneBytes,
            'leftUntilDoneBytes' => $this->leftUntilDoneBytes,
            'downloadedBytes' => $this->downloadedBytes,
            'uploadedBytes' => $this->uploadedBytes,
            'peersConnected' => $this->peersConnected,
            'peersSendingToUs' => $this->peersSendingToUs,
            'peersGettingFromUs' => $this->peersGettingFromUs,
            'queuePosition' => $this->queuePosition,
            'isStalled' => $this->isStalled,
            'isFinished' => $this->isFinished,
            'isPaused' => $this->status->isPaused(),
            'downloadDir' => $this->downloadDir,
            'error' => $this->error,
            'addedAt' => $this->addedAt?->toIso8601String(),
            'doneAt' => $this->doneAt?->toIso8601String(),
            'labels' => $this->labels,
        ];
    }
}

<?php

namespace App\Enums;

/**
 * Everything the RPC lets us do to a torrent that neither adds nor removes it.
 */
enum TorrentAction: string
{
    case Start = 'start';
    case StartNow = 'start-now';
    case Stop = 'stop';
    case Verify = 'verify';
    case Reannounce = 'reannounce';
    case QueueTop = 'queue-top';
    case QueueUp = 'queue-up';
    case QueueDown = 'queue-down';
    case QueueBottom = 'queue-bottom';

    public function rpcMethod(): string
    {
        return match ($this) {
            self::Start => 'torrent-start',
            self::StartNow => 'torrent-start-now',
            self::Stop => 'torrent-stop',
            self::Verify => 'torrent-verify',
            self::Reannounce => 'torrent-reannounce',
            self::QueueTop => 'queue-move-top',
            self::QueueUp => 'queue-move-up',
            self::QueueDown => 'queue-move-down',
            self::QueueBottom => 'queue-move-bottom',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Start => 'Запущено',
            self::StartNow => 'Запущено поза чергою',
            self::Stop => 'Зупинено',
            self::Verify => 'Перевірку розпочато',
            self::Reannounce => 'Анонс надіслано',
            self::QueueTop => 'Переміщено на початок черги',
            self::QueueUp => 'Піднято в черзі',
            self::QueueDown => 'Опущено в черзі',
            self::QueueBottom => 'Переміщено в кінець черги',
        };
    }
}

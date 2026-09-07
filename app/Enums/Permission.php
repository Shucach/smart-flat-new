<?php

namespace App\Enums;

enum Permission: string
{
    case MediaView = 'media.view';
    case MediaDelete = 'media.delete';
    case FrameView = 'frame.view';
    case FrameUpload = 'frame.upload';
    case FrameDelete = 'frame.delete';
    case FrameRestart = 'frame.restart';
    case TorrentView = 'torrent.view';
    case TorrentAdd = 'torrent.add';
    case TorrentManage = 'torrent.manage';
    case TorrentDelete = 'torrent.delete';
    case SystemView = 'system.view';
    case SystemPower = 'system.power';
    case UsersManage = 'users.manage';

    public function label(): string
    {
        return match ($this) {
            self::MediaView => 'Перегляд медіа',
            self::MediaDelete => 'Видалення медіа',
            self::FrameView => 'Перегляд рамки',
            self::FrameUpload => 'Завантаження в рамку',
            self::FrameDelete => 'Видалення з рамки',
            self::FrameRestart => 'Перезапуск показу рамки',
            self::TorrentView => 'Перегляд торентів',
            self::TorrentAdd => 'Додавання торентів',
            self::TorrentManage => 'Керування торентами',
            self::TorrentDelete => 'Видалення торентів',
            self::SystemView => 'Перегляд системи',
            self::SystemPower => 'Перезавантаження та вимкнення',
            self::UsersManage => 'Керування користувачами',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::MediaView, self::MediaDelete => 'Медіа',
            self::FrameView, self::FrameUpload, self::FrameDelete, self::FrameRestart => 'Розумна рамка',
            self::TorrentView, self::TorrentAdd, self::TorrentManage, self::TorrentDelete => 'Торенти',
            self::SystemView, self::SystemPower => 'Система',
            self::UsersManage => 'Адміністрування',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $permission): string => $permission->value, self::cases());
    }
}

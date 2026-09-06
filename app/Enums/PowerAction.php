<?php

namespace App\Enums;

enum PowerAction: string
{
    case Reboot = 'reboot';
    case Shutdown = 'shutdown';

    public function label(): string
    {
        return match ($this) {
            self::Reboot => 'Перезавантаження',
            self::Shutdown => 'Вимкнення',
        };
    }

    public function commandConfigKey(): string
    {
        return match ($this) {
            self::Reboot => 'smartflat.system.reboot_command',
            self::Shutdown => 'smartflat.system.shutdown_command',
        };
    }
}

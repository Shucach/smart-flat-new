<?php

namespace App\Modules\System\Exceptions;

use App\Enums\PowerAction;
use RuntimeException;

class PowerException extends RuntimeException
{
    public static function notConfigured(PowerAction $action): self
    {
        return new self("Команду для дії [{$action->value}] не налаштовано.");
    }

    public static function failed(PowerAction $action, string $reason): self
    {
        return new self("Не вдалося виконати дію [{$action->value}]: {$reason}");
    }
}

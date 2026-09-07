<?php

namespace App\Modules\System\Services;

use App\Enums\PowerAction;
use App\Modules\System\Contracts\PowerManager;
use App\Modules\System\Exceptions\PowerException;

/**
 * Refuses the action, for hosts where power control is deliberately off.
 *
 * It reports the refusal rather than swallowing it: a button that answers
 * "команду надіслано" and then leaves the server running is worse than one that
 * says the driver is not configured.
 */
final readonly class NullPowerManager implements PowerManager
{
    public function perform(PowerAction $action): void
    {
        throw PowerException::disabled($action);
    }
}

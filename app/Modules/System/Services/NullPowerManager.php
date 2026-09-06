<?php

namespace App\Modules\System\Services;

use App\Enums\PowerAction;
use App\Modules\System\Contracts\PowerManager;
use Illuminate\Support\Facades\Log;

/**
 * Records the requested action without touching the host.
 */
final readonly class NullPowerManager implements PowerManager
{
    public function perform(PowerAction $action): void
    {
        Log::info('Запит на керування живленням проігноровано.', ['action' => $action->value]);
    }
}

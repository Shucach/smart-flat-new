<?php

use App\Enums\PowerAction;
use App\Modules\System\Exceptions\PowerException;
use App\Modules\System\Services\NullPowerManager;

it('reports that power control is off instead of pretending it worked', function () {
    expect(fn () => (new NullPowerManager)->perform(PowerAction::Reboot))
        ->toThrow(PowerException::class, 'Керування живленням вимкнено');
});

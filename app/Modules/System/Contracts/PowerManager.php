<?php

namespace App\Modules\System\Contracts;

use App\Enums\PowerAction;
use App\Modules\System\Exceptions\PowerException;

interface PowerManager
{
    /**
     * @throws PowerException
     */
    public function perform(PowerAction $action): void;
}

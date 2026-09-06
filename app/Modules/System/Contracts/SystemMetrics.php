<?php

namespace App\Modules\System\Contracts;

use App\Modules\System\Data\SystemSnapshot;

interface SystemMetrics
{
    public function snapshot(): SystemSnapshot;
}

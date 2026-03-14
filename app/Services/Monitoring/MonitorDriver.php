<?php

namespace App\Services\Monitoring;

use App\Models\Monitor;

interface MonitorDriver
{
    public function check(Monitor $monitor): MonitorResult;
}


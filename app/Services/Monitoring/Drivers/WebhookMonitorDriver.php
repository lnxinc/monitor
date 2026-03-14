<?php

namespace App\Services\Monitoring\Drivers;

use App\Models\Monitor;
use App\Services\Monitoring\MonitorDriver;
use App\Services\Monitoring\MonitorResult;

class WebhookMonitorDriver implements MonitorDriver
{
    public function check(Monitor $monitor): MonitorResult
    {
        return MonitorResult::skip('Webhook monitors rely on inbound events');
    }
}


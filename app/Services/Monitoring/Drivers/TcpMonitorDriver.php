<?php

namespace App\Services\Monitoring\Drivers;

use App\Models\Monitor;
use App\Services\Monitoring\MonitorDriver;
use App\Services\Monitoring\MonitorResult;
use Illuminate\Support\Arr;

class TcpMonitorDriver implements MonitorDriver
{
    public function check(Monitor $monitor): MonitorResult
    {
        $host = $monitor->config['host'] ?? $monitor->url;
        $port = (int) Arr::get($monitor->config, 'port', 0);
        $timeout = (int) Arr::get($monitor->config, 'timeout', 10);

        if (! $host || $port < 1 || $port > 65535) {
            return MonitorResult::failure('Invalid host or port');
        }

        $start = microtime(true);
        $errno = 0;
        $errstr = null;
        $connection = @fsockopen($host, $port, $errno, $errstr, max(1, $timeout));
        $duration = (int) round((microtime(true) - $start) * 1000);

        if (! $connection) {
            $message = $errstr ?: 'Connection failed';
            $meta = ['errno' => $errno, 'host' => $host, 'port' => $port];
            return MonitorResult::failure($message, $meta, null, $duration);
        }

        fclose($connection);

        $meta = ['host' => $host, 'port' => $port];
        return MonitorResult::success($meta, null, $duration);
    }
}


<?php

namespace App\Services\Monitoring\Drivers;

use App\Models\Monitor;
use App\Services\Monitoring\MonitorDriver;
use App\Services\Monitoring\MonitorResult;
use Illuminate\Support\Arr;

class PingMonitorDriver implements MonitorDriver
{
    public function check(Monitor $monitor): MonitorResult
    {
        $target = $monitor->config['host'] ?? $monitor->url;
        if (! $target) {
            return MonitorResult::failure('Missing host');
        }

        if (! preg_match('/^[A-Za-z0-9_.:-]+$/', $target)) {
            return MonitorResult::failure('Invalid host characters');
        }

        $count = (int) Arr::get($monitor->config, 'count', 4);
        $timeout = (int) Arr::get($monitor->config, 'timeout', 5);

        $command = PHP_OS_FAMILY === 'Windows'
            ? sprintf('ping -n %d -w %d %s', max(1, $count), max(1000, $timeout * 1000), escapeshellarg($target))
            : sprintf('ping -c %d -W %d %s', max(1, $count), max(1, $timeout), escapeshellarg($target));

        $start = microtime(true);
        [$exitCode, $output] = $this->runShellCommand($command);
        $elapsed = (int) round((microtime(true) - $start) * 1000);

        if ($exitCode !== 0) {
            return MonitorResult::failure('Ping failed', ['output' => $output], null, $elapsed);
        }

        $meta = $this->parsePingOutput($output);

        return MonitorResult::success($meta, null, $elapsed);
    }

    private function runShellCommand(string $command): array
    {
        $descriptor = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptor, $pipes);
        if (! is_resource($process)) {
            return [1, 'Failed to start ping command'];
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($process);

        if ($exitCode === -1 && ! empty($stderr)) {
            $exitCode = 1;
        }

        $output = trim($stdout ?: $stderr);
        return [$exitCode, $output];
    }

    private function parsePingOutput(string $output): array
    {
        $meta = ['raw_output' => $output];

        if (preg_match('/=\s*([\d.]+)/', $output, $avg)) {
            $meta['rtt_avg_ms'] = (float) $avg[1];
        }

        if (preg_match('/(\d+)%\s*packet loss/', $output, $loss)) {
            $meta['packet_loss_percent'] = (int) $loss[1];
        }

        if (preg_match('/time[=<]\s*(\d+\.?\d*)ms/', $output, $time)) {
            $meta['last_reply_ms'] = (float) $time[1];
        }

        return $meta;
    }
}

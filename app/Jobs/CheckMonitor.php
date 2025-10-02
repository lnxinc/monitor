<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Services\Monitoring\MonitorDriverManager;
use App\Services\Monitoring\MonitorResult;
use App\Services\Monitoring\MonitorStateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckMonitor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Monitor $monitor) {}

    public function handle(): void
    {
        $monitor = $this->monitor->fresh();
        if (! $monitor) return;

        try {
            $driver = app(MonitorDriverManager::class)->driver($monitor->type ?? 'http');
        } catch (\Throwable $e) {
            Log::error('Monitor driver resolution failed', ['monitor_id' => $monitor->id, 'error' => $e->getMessage()]);
            $driver = null;
        }

        $result = $driver
            ? $this->runDriver($driver, $monitor)
            : MonitorResult::failure('Driver not found');

        app(MonitorStateService::class)->apply($monitor, $result);
    }

    private function runDriver(?object $driver, Monitor $monitor): MonitorResult
    {
        if (! $driver instanceof \App\Services\Monitoring\MonitorDriver) {
            return MonitorResult::failure('Invalid driver instance');
        }

        try {
            return $driver->check($monitor);
        } catch (\Throwable $e) {
            Log::error('Monitor driver execution failed', [
                'monitor_id' => $monitor->id,
                'type' => $monitor->type,
                'error' => $e->getMessage(),
            ]);

            return MonitorResult::failure($e->getMessage());
        }
    }
}

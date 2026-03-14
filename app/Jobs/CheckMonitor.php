<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Services\Monitoring\HeartbeatCollector;
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
        if (! $monitor) {
            return;
        }

        try {
            $collector = app(HeartbeatCollector::class);
            $result = $collector->collect($monitor);
        } catch (\Throwable $e) {
            Log::error('Heartbeat collection failed', [
                'monitor_id' => $monitor->id,
                'type' => $monitor->type,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        app(MonitorStateService::class)->apply($monitor, $result);
    }
}

<?php

namespace App\Services\Alerting;

use App\Jobs\SendNotification;
use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class NotificationBus
{
    public function dispatchMonitorEvent(Monitor $monitor, string $event, array $context = []): void
    {
        if ($this->inMaintenance($monitor)) {
            Log::info('Notification suppressed due to maintenance window', [
                'monitor_id' => $monitor->id,
                'event' => $event,
            ]);

            return;
        }

        if ($this->isDeduplicated($monitor, $event, $context)) {
            Log::info('Notification deduped', [
                'monitor_id' => $monitor->id,
                'event' => $event,
            ]);

            return;
        }

        $policies = $monitor->policies()->with(['channels' => function ($q) {
            $q->where('is_active', true);
        }])->where('enabled', true)->get();

        if ($policies->isEmpty()) {
            return;
        }

        foreach ($policies as $policy) {
            if ($event === 'down' && ($monitor->consecutive_failures ?? 0) < ($policy->down_threshold ?? 1)) {
                continue;
            }
            if ($event === 'up' && ! ($policy->notify_on_recovery ?? true)) {
                continue;
            }

            foreach ($policy->channels as $channel) {
                $channelKey = $this->channelKey($channel->id, $channel->type);

                if ($this->isFlooded($channelKey)) {
                    Log::warning('Notification skipped due to flood control', [
                        'monitor_id' => $monitor->id,
                        'event' => $event,
                        'channel_id' => $channel->id,
                        'channel_type' => $channel->type,
                    ]);

                    continue;
                }

                $this->recordChannelAttempt($channelKey);

                SendNotification::dispatch($channel, $monitor, $event, $context);
            }
        }
    }

    protected function isDeduplicated(Monitor $monitor, string $event, array $context): bool
    {
        $ttl = (int) config('alerting.bus.dedupe_ttl_seconds', 300);
        if ($ttl <= 0) {
            return false;
        }

        $fingerprint = $context;
        ksort($fingerprint);
        $hash = md5(json_encode($fingerprint));
        $key = sprintf('alert:dedupe:%s:%s:%s', $monitor->id, $event, $hash);

        return ! Cache::add($key, Carbon::now()->timestamp, $ttl);
    }

    protected function isFlooded(string $channelKey): bool
    {
        $limit = (int) config('alerting.bus.flood.limit', 10);
        if ($limit <= 0) {
            return false;
        }

        return RateLimiter::tooManyAttempts($channelKey, $limit);
    }

    protected function recordChannelAttempt(string $channelKey): void
    {
        $window = (int) config('alerting.bus.flood.window_seconds', 60);
        RateLimiter::hit($channelKey, $window);
    }

    protected function channelKey(?int $id, string $type): string
    {
        return sprintf('alert:channel:%s:%s', $type, $id ?? 'na');
    }

    protected function inMaintenance(Monitor $monitor): bool
    {
        $now = Carbon::now();

        return MaintenanceWindow::query()
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->where(function ($q) use ($monitor) {
                $q->where('scope', 'global')
                    ->orWhere(function ($q2) use ($monitor) {
                        $q2->where('scope', 'monitor')->where('ref_id', $monitor->id);
                    });
            })
            ->exists();
    }
}

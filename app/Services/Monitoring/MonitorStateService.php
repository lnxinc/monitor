<?php

namespace App\Services\Monitoring;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Services\Alerting\NotificationBus;
use Illuminate\Support\Facades\Log;

class MonitorStateService
{
    public function apply(Monitor $monitor, MonitorResult $result, bool $notify = true): void
    {
        $prevStatus = $monitor->status ?? 'unknown';

        $checkedAt = $result->checkedAt ?? now();

        if ($result->recordHistory) {
            MonitorCheck::create([
                'monitor_id' => $monitor->id,
                'status' => $result->status === 'up' ? 'up' : 'down',
                'status_code' => $result->statusCode,
                'response_time_ms' => $result->responseTimeMs,
                'error' => $result->error,
                'meta' => $result->meta ?: null,
                'payload' => $result->payload,
                'checked_at' => $checkedAt,
            ]);
        }

        if (! $result->recordHistory) {
            return;
        }

        $fields = [
            'status' => $result->status,
            'last_status_code' => $result->statusCode,
            'last_response_time_ms' => $result->responseTimeMs,
            'last_checked_at' => $checkedAt,
            'failure_reason' => $result->error,
        ];

        if ($result->sslValidFrom) {
            $fields['ssl_valid_from'] = $result->sslValidFrom;
        }
        if ($result->sslExpiresAt) {
            $fields['ssl_expires_at'] = $result->sslExpiresAt;
        }
        if ($result->sslIssuer) {
            $fields['ssl_issuer'] = $result->sslIssuer;
        }

        if ($result->status === 'down') {
            $fields['consecutive_failures'] = ($monitor->consecutive_failures ?? 0) + 1;
        } else {
            $fields['consecutive_failures'] = 0;
        }

        $monitor->update($fields);

        if (! $notify || $prevStatus === $result->status) {
            return;
        }

        try {
            app(NotificationBus::class)->dispatchMonitorEvent($monitor->fresh(), $result->status === 'down' ? 'down' : 'up', [
                'status_code' => $result->statusCode,
                'response_time_ms' => $result->responseTimeMs,
                'error' => $result->error,
                'meta' => $result->meta,
            ]);
        } catch (\Throwable $e) {
            Log::error('Monitor notification failed', [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

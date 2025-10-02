<?php

namespace App\Services\Alerting;

use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notifier
{
    public static function monitorEvent(Monitor $monitor, string $event, array $context = []): void
    {
        // Suppress during maintenance windows
        if (self::inMaintenance($monitor)) {
            Log::info('Notifier suppressed due to maintenance window', ['monitor_id' => $monitor->id, 'event' => $event]);
            return;
        }

        $policies = $monitor->policies()->with(['channels' => function ($q) {
            $q->where('is_active', true);
        }])->where('enabled', true)->get();
        if ($policies->isEmpty()) return;

        foreach ($policies as $policy) {
            // Enforce basic policy conditions
            if ($event === 'down' && ($monitor->consecutive_failures ?? 0) < ($policy->down_threshold ?? 1)) {
                continue;
            }
            if ($event === 'up' && !($policy->notify_on_recovery ?? true)) {
                continue;
            }

            foreach ($policy->channels as $channel) {
                try {
                    match ($channel->type) {
                        'email' => self::sendEmail($channel->config['email'] ?? null, $monitor, $event, $context),
                        'slack_webhook' => self::sendSlack($channel->config['webhook_url'] ?? null, $monitor, $event, $context),
                        default => null,
                    };
                } catch (\Throwable $e) {
                    Log::error('Notifier failed', ['error' => $e->getMessage(), 'monitor_id' => $monitor->id]);
                }
            }
        }
    }

    private static function sendEmail(?string $email, Monitor $monitor, string $event, array $ctx): void
    {
        if (!$email) return;
        $subject = match ($event) {
            'down' => "[ALERT] {$monitor->name} is DOWN",
            'up' => "[RECOVERY] {$monitor->name} is UP",
            default => "[Monitor] {$monitor->name} {$event}",
        };
        $body = self::formatBody($monitor, $event, $ctx);
        Mail::raw($body, function ($m) use ($email, $subject) {
            $m->to($email)->subject($subject);
        });
    }

    private static function sendSlack(?string $webhookUrl, Monitor $monitor, string $event, array $ctx): void
    {
        if (!$webhookUrl) return;
        $color = $event === 'down' ? '#E11D48' : '#16A34A';
        $body = [
            'attachments' => [[
                'color' => $color,
                'title' => $event === 'down' ? "ALERT: {$monitor->name} is DOWN" : "RECOVERY: {$monitor->name} is UP",
                'text' => self::formatBody($monitor, $event, $ctx),
                'mrkdwn_in' => ['text']
            ]],
        ];
        Http::asJson()->post($webhookUrl, $body);
    }

    private static function formatBody(Monitor $m, string $event, array $ctx): string
    {
        $lines = [
            "Name: {$m->name}",
            "URL: {$m->url}",
            "Event: {$event}",
        ];
        if (!empty($ctx['status_code'])) $lines[] = 'Status code: '.$ctx['status_code'];
        if (!empty($ctx['response_time_ms'])) $lines[] = 'Response time: '.$ctx['response_time_ms'].' ms';
        if (!empty($ctx['error'])) $lines[] = 'Error: '.$ctx['error'];
        return implode("\n", $lines);
    }

    private static function inMaintenance(Monitor $monitor): bool
    {
        $now = now();
        $active = MaintenanceWindow::query()
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->where(function ($q) use ($monitor) {
                $q->where('scope', 'global')
                  ->orWhere(function ($q2) use ($monitor) { $q2->where('scope', 'monitor')->where('ref_id', $monitor->id); });
            })
            ->exists();
        return $active;
    }
}

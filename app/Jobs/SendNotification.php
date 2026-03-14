<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\NotificationChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public NotificationChannel $channel,
        public Monitor $monitor,
        public string $event,
        public array $context = [],
    ) {}

    public function handle(): void
    {
        match ($this->channel->type) {
            'email' => $this->sendEmail(),
            'slack_webhook' => $this->sendSlack(),
            'webhook' => $this->sendWebhook($this->channel->config['url'] ?? null),
            default => null,
        };
    }

    protected function sendEmail(): void
    {
        $email = $this->channel->config['email'] ?? null;
        if (! $email) {
            return;
        }

        $subject = match ($this->event) {
            'down' => "[ALERT] {$this->monitor->name} is DOWN",
            'up' => "[RECOVERY] {$this->monitor->name} is UP",
            default => "[Monitor] {$this->monitor->name} {$this->event}",
        };

        $body = $this->formatBody();
        Mail::raw($body, function ($m) use ($email, $subject) {
            $m->to($email)->subject($subject);
        });
    }

    protected function sendSlack(): void
    {
        $webhookUrl = $this->channel->config['webhook_url'] ?? null;
        if (! $webhookUrl) {
            return;
        }

        $mode = strtolower($this->channel->config['format'] ?? $this->channel->config['delivery'] ?? 'slack');
        if ($mode === 'generic' || $mode === 'webhook') {
            $this->sendWebhook($webhookUrl);

            return;
        }

        $color = $this->event === 'down' ? '#E11D48' : '#16A34A';
        $body = [
            'attachments' => [[
                'color' => $color,
                'title' => $this->event === 'down' ? "ALERT: {$this->monitor->name} is DOWN" : "RECOVERY: {$this->monitor->name} is UP",
                'text' => $this->formatBody(),
                'mrkdwn_in' => ['text'],
            ]],
        ];

        Http::asJson()->post($webhookUrl, $body);
    }

    protected function sendWebhook(?string $url): void
    {
        if (! $url) {
            return;
        }

        $payload = [
            'event' => $this->event,
            'monitor' => [
                'id' => $this->monitor->id,
                'name' => $this->monitor->name,
                'status' => $this->monitor->status,
                'target' => $this->monitor->display_target,
            ],
            'context' => $this->context,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];

        Http::asJson()->post($url, $payload);
    }

    protected function formatBody(): string
    {
        $lines = [
            "Name: {$this->monitor->name}",
            "Target: {$this->monitor->display_target}",
            "Event: {$this->event}",
        ];

        if (! empty($this->context['status_code'])) {
            $lines[] = 'Status code: '.$this->context['status_code'];
        }
        if (! empty($this->context['response_time_ms'])) {
            $lines[] = 'Response time: '.$this->context['response_time_ms'].' ms';
        }
        if (! empty($this->context['error'])) {
            $lines[] = 'Error: '.$this->context['error'];
        }

        return implode("\n", $lines);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotification job failed permanently', [
            'channel_id' => $this->channel->id,
            'channel_type' => $this->channel->type,
            'monitor_id' => $this->monitor->id,
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

use App\Jobs\SendNotification;
use App\Models\Monitor;
use App\Models\MonitorPolicy;
use App\Models\NotificationChannel;
use App\Services\Alerting\NotificationBus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Cache::flush();
    config(['alerting.bus.dedupe_ttl_seconds' => 300]);
    config(['alerting.bus.flood.limit' => 10]);
    config(['alerting.bus.flood.window_seconds' => 60]);
});

test('notification bus dispatches job for active channel', function () {
    Queue::fake();

    $monitor = Monitor::factory()->create([
        'status' => 'down',
        'consecutive_failures' => 2,
    ]);

    $channel = NotificationChannel::create([
        'name' => 'NOC Slack',
        'type' => 'slack_webhook',
        'config' => ['webhook_url' => 'https://hooks.slack.test/example'],
        'is_active' => true,
    ]);

    $policy = MonitorPolicy::create([
        'monitor_id' => $monitor->id,
        'down_threshold' => 1,
        'notify_after_seconds' => 0,
        'repeat_interval_minutes' => null,
        'notify_on_recovery' => true,
        'enabled' => true,
    ]);

    $policy->channels()->attach($channel);

    $bus = app(NotificationBus::class);
    $bus->dispatchMonitorEvent($monitor->fresh(), 'down', ['error' => 'timeout']);

    Queue::assertPushed(SendNotification::class, 1);
});

test('notification bus deduplicates repeated monitor events', function () {
    Queue::fake();

    $monitor = Monitor::factory()->create([
        'status' => 'down',
        'consecutive_failures' => 2,
    ]);

    $channel = NotificationChannel::create([
        'name' => 'NOC Slack',
        'type' => 'slack_webhook',
        'config' => ['webhook_url' => 'https://hooks.slack.test/example'],
        'is_active' => true,
    ]);

    $policy = MonitorPolicy::create([
        'monitor_id' => $monitor->id,
        'down_threshold' => 1,
        'notify_after_seconds' => 0,
        'repeat_interval_minutes' => null,
        'notify_on_recovery' => true,
        'enabled' => true,
    ]);

    $policy->channels()->attach($channel);

    $bus = app(NotificationBus::class);

    $bus->dispatchMonitorEvent($monitor->fresh(), 'down', ['error' => 'timeout']);
    $bus->dispatchMonitorEvent($monitor->fresh(), 'down', ['error' => 'timeout']);

    Queue::assertPushed(SendNotification::class, 1);
});

test('notification bus enforces flood control per channel', function () {
    Queue::fake();
    config(['alerting.bus.dedupe_ttl_seconds' => 0]);
    config(['alerting.bus.flood.limit' => 2]);
    config(['alerting.bus.flood.window_seconds' => 120]);

    $monitor = Monitor::factory()->create([
        'status' => 'down',
        'consecutive_failures' => 5,
    ]);

    $channel = NotificationChannel::create([
        'name' => 'Webhook sink',
        'type' => 'slack_webhook',
        'config' => [
            'webhook_url' => 'https://alerts.example.test/hook',
            'format' => 'generic',
        ],
        'is_active' => true,
    ]);

    $policy = MonitorPolicy::create([
        'monitor_id' => $monitor->id,
        'down_threshold' => 1,
        'notify_after_seconds' => 0,
        'repeat_interval_minutes' => null,
        'notify_on_recovery' => true,
        'enabled' => true,
    ]);
    $policy->channels()->attach($channel);

    $bus = app(NotificationBus::class);

    $bus->dispatchMonitorEvent($monitor->fresh(), 'down', ['error' => 'timeout']);
    $bus->dispatchMonitorEvent($monitor->fresh(), 'down', ['error' => 'timeout']);
    $bus->dispatchMonitorEvent($monitor->fresh(), 'down', ['error' => 'timeout']);

    Queue::assertPushed(SendNotification::class, 2);

    $channelKey = sprintf('alert:channel:%s:%s', $channel->type, $channel->id);
    RateLimiter::clear($channelKey);
});

test('notification bus skips channels below down threshold', function () {
    Queue::fake();

    $monitor = Monitor::factory()->create([
        'status' => 'down',
        'consecutive_failures' => 1,
    ]);

    $channel = NotificationChannel::create([
        'name' => 'High Threshold',
        'type' => 'email',
        'config' => ['email' => 'ops@example.com'],
        'is_active' => true,
    ]);

    $policy = MonitorPolicy::create([
        'monitor_id' => $monitor->id,
        'down_threshold' => 5,
        'notify_after_seconds' => 0,
        'repeat_interval_minutes' => null,
        'notify_on_recovery' => true,
        'enabled' => true,
    ]);
    $policy->channels()->attach($channel);

    $bus = app(NotificationBus::class);
    $bus->dispatchMonitorEvent($monitor->fresh(), 'down', ['error' => 'timeout']);

    Queue::assertNotPushed(SendNotification::class);
});

test('notification bus skips recovery when policy disables it', function () {
    Queue::fake();

    $monitor = Monitor::factory()->create([
        'status' => 'up',
        'consecutive_failures' => 0,
    ]);

    $channel = NotificationChannel::create([
        'name' => 'No Recovery',
        'type' => 'email',
        'config' => ['email' => 'ops@example.com'],
        'is_active' => true,
    ]);

    $policy = MonitorPolicy::create([
        'monitor_id' => $monitor->id,
        'down_threshold' => 1,
        'notify_after_seconds' => 0,
        'repeat_interval_minutes' => null,
        'notify_on_recovery' => false,
        'enabled' => true,
    ]);
    $policy->channels()->attach($channel);

    $bus = app(NotificationBus::class);
    $bus->dispatchMonitorEvent($monitor->fresh(), 'up', []);

    Queue::assertNotPushed(SendNotification::class);
});

<?php

use App\Jobs\CheckMonitor;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Support\Carbon;

it('records webhook events and updates monitor state', function () {
    $monitor = Monitor::factory()->webhook()->create();

    $timestamp = Carbon::now()->subMinutes(5);

    $response = $this->postJson(route('api.monitors.webhooks.ingest', ['token' => $monitor->webhook_token]), [
        'status' => 'down',
        'response_time_ms' => 2500,
        'error' => 'Connection timeout',
        'meta' => ['probe' => 'edge-1'],
        'payload' => ['detail' => 'Spectrum portal unreachable'],
        'checked_at' => $timestamp->toIso8601String(),
    ]);

    $response->assertStatus(202);

    $monitor->refresh();

    expect($monitor->status)->toBe('down')
        ->and($monitor->failure_reason)->toBe('Connection timeout')
        ->and($monitor->consecutive_failures)->toBe(1)
        ->and($monitor->last_checked_at?->toIso8601String())->toBe($timestamp->toIso8601String());

    $check = MonitorCheck::where('monitor_id', $monitor->id)->first();

    expect($check)->not->toBeNull()
        ->and($check->status)->toBe('down')
        ->and($check->meta)->toMatchArray(['probe' => 'edge-1'])
        ->and($check->payload)->toMatchArray(['detail' => 'Spectrum portal unreachable']);

    $this->postJson(route('api.monitors.webhooks.ingest', ['token' => $monitor->webhook_token]), [
        'status' => 'up',
        'response_time_ms' => 320,
    ])->assertStatus(202);

    $monitor->refresh();

    expect($monitor->status)->toBe('up')
        ->and($monitor->consecutive_failures)->toBe(0)
        ->and($monitor->failure_reason)->toBeNull();
});

it('returns not found for unknown webhook token', function () {
    $this->postJson(route('api.monitors.webhooks.ingest', ['token' => 'invalid-token']), [
        'status' => 'up',
    ])->assertStatus(404);
});

it('skips active checks for webhook monitors', function () {
    $monitor = Monitor::factory()->webhook()->create();

    CheckMonitor::dispatchSync($monitor);

    expect(MonitorCheck::where('monitor_id', $monitor->id)->count())->toBe(0);
});

<?php

use App\Jobs\CheckMonitor;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Services\Monitoring\Drivers\HttpMonitorDriver;
use App\Services\Monitoring\HeartbeatCollector;
use App\Services\Monitoring\MonitorDriver;
use App\Services\Monitoring\MonitorDriverManager;
use App\Services\Monitoring\MonitorResult;

beforeEach(function () {
    config(['monitoring.heartbeat.max_attempts' => 3]);
    config(['monitoring.heartbeat.initial_backoff_ms' => 100]);
    config(['monitoring.heartbeat.backoff_multiplier' => 2]);
});

afterEach(function () {
    app()->forgetInstance(HeartbeatCollector::class);
    app(MonitorDriverManager::class)->register('http', HttpMonitorDriver::class);
    FlakyDriver::fake([]);
});

test('heartbeat collector retries transient failures before succeeding', function () {
    $monitor = Monitor::factory()->create([
        'type' => 'http',
        'frequency_minutes' => 1,
    ]);

    FlakyDriver::fake([
        MonitorResult::failure('timeout', ['retryable' => true]),
        MonitorResult::success([], 200, 180),
    ]);

    $manager = app(MonitorDriverManager::class);
    $manager->register('http', FlakyDriver::class);

    $collector = new HeartbeatCollector($manager);
    $backoffs = [];
    $collector->setSleeper(function (int $milliseconds) use (&$backoffs) {
        $backoffs[] = $milliseconds;
    });
    app()->instance(HeartbeatCollector::class, $collector);

    (new CheckMonitor($monitor))->handle();

    $monitor->refresh();

    expect(FlakyDriver::$calls)->toBe(2);
    expect($backoffs)->toEqual([100]);
    expect($monitor->status)->toBe('up');
    expect($monitor->consecutive_failures)->toBe(0);
});

test('heartbeat collector stops after max attempts and marks monitor down', function () {
    config(['monitoring.heartbeat.initial_backoff_ms' => 50]);
    config(['monitoring.heartbeat.backoff_multiplier' => 3]);

    $monitor = Monitor::factory()->create([
        'type' => 'http',
        'frequency_minutes' => 1,
    ]);

    FlakyDriver::fake([
        MonitorResult::failure('timeout', ['retryable' => true]),
        MonitorResult::failure('connection reset', ['retryable' => true]),
        MonitorResult::failure('hard failure', ['retryable' => false]),
    ]);

    $manager = app(MonitorDriverManager::class);
    $manager->register('http', FlakyDriver::class);

    $collector = new HeartbeatCollector($manager);
    $backoffs = [];
    $collector->setSleeper(function (int $milliseconds) use (&$backoffs) {
        $backoffs[] = $milliseconds;
    });
    app()->instance(HeartbeatCollector::class, $collector);

    (new CheckMonitor($monitor))->handle();

    $monitor->refresh();

    expect(FlakyDriver::$calls)->toBe(3);
    expect($backoffs)->toEqual([50, 150]);
    expect($monitor->status)->toBe('down');
    expect($monitor->consecutive_failures)->toBe(1);
    expect(MonitorCheck::where('monitor_id', $monitor->id)->count())->toBe(1);
});

class FlakyDriver implements MonitorDriver
{
    public static array $sequence = [];

    public static int $calls = 0;

    public static function fake(array $sequence): void
    {
        static::$sequence = array_values($sequence);
        static::$calls = 0;
    }

    public function check(Monitor $monitor): MonitorResult
    {
        $index = static::$calls++;
        if (! array_key_exists($index, static::$sequence)) {
            return MonitorResult::failure('unexpected call', ['retryable' => false]);
        }

        return static::$sequence[$index];
    }
}

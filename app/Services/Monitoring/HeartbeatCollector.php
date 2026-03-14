<?php

namespace App\Services\Monitoring;

use App\Models\Monitor;
use Illuminate\Support\Facades\Log;

class HeartbeatCollector
{
    /** @var callable */
    protected $sleeper;

    public function __construct(protected MonitorDriverManager $driverManager)
    {
        $this->sleeper = static function (int $milliseconds): void {
            if ($milliseconds <= 0) {
                return;
            }

            usleep($milliseconds * 1000);
        };
    }

    public function collect(Monitor $monitor): MonitorResult
    {
        $maxAttempts = (int) config('monitoring.heartbeat.max_attempts', 3);
        $backoff = (int) config('monitoring.heartbeat.initial_backoff_ms', 200);
        $multiplier = (float) config('monitoring.heartbeat.backoff_multiplier', 2.0);

        $attempt = 0;
        $result = MonitorResult::failure('No driver available', ['retryable' => false])->withCheckedAt(now());

        while ($attempt < max(1, $maxAttempts)) {
            $attempt++;

            $result = $this->runOnce($monitor, $attempt);

            if (! $this->shouldRetry($result, $attempt, $maxAttempts)) {
                break;
            }

            ($this->sleeper)(max(0, $backoff));
            $backoff = (int) max(1, round($backoff * $multiplier));
        }

        return $result;
    }

    public function setSleeper(callable $sleeper): void
    {
        $this->sleeper = $sleeper;
    }

    protected function runOnce(Monitor $monitor, int $attempt): MonitorResult
    {
        $driver = null;

        try {
            $driver = $this->driverManager->driver($monitor->type ?? 'http');
        } catch (\Throwable $e) {
            Log::error('Heartbeat collector failed to resolve monitor driver', [
                'monitor_id' => $monitor->id,
                'type' => $monitor->type,
                'error' => $e->getMessage(),
            ]);
        }

        if (! $driver instanceof MonitorDriver) {
            return MonitorResult::failure('Driver not available', ['retryable' => false])->withCheckedAt(now());
        }

        try {
            $result = $driver->check($monitor);
        } catch (\Throwable $e) {
            Log::warning('Heartbeat collector driver threw exception', [
                'monitor_id' => $monitor->id,
                'type' => $monitor->type,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);

            return MonitorResult::failure($e->getMessage(), [
                'retryable' => $this->looksRetryable($e->getMessage()),
                'exception' => get_class($e),
            ])->withCheckedAt(now());
        }

        if (! $result->checkedAt) {
            $result = $result->withCheckedAt(now());
        }

        return $result;
    }

    protected function shouldRetry(MonitorResult $result, int $attempt, int $maxAttempts): bool
    {
        if ($attempt >= $maxAttempts) {
            return false;
        }

        if ($result->status === 'up') {
            return false;
        }

        if (! $result->recordHistory) {
            return false;
        }

        return $result->isRetryable();
    }

    protected function looksRetryable(?string $message): bool
    {
        if (! $message) {
            return false;
        }

        $message = strtolower($message);
        foreach (['timeout', 'timed out', 'temporarily', 'connection reset', 'connection refused'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonitor;
use App\Models\Monitor;
use Illuminate\Console\Command;

class RunDueMonitors extends Command
{
    protected $signature = 'monitors:run';
    protected $description = 'Dispatch checks for monitors that are due based on their frequency.';

    public function handle(): int
    {
        $now = now();
        $monitors = Monitor::query()
            ->where('type', '!=', 'webhook')
            ->get();
        $due = $monitors->filter(function ($m) use ($now) {
            if (empty($m->frequency_minutes) || $m->frequency_minutes < 1) return false;
            if (is_null($m->last_checked_at)) return true;
            return $m->last_checked_at->lte($now->copy()->subMinutes($m->frequency_minutes));
        });

        $count = 0;
        foreach ($due as $monitor) {
            CheckMonitor::dispatch($monitor);
            $count++;
        }

        $this->info("Dispatched checks for {$count} monitors.");
        return Command::SUCCESS;
    }
}

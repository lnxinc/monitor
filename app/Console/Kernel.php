<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Run due monitors every minute
        $schedule->command('monitors:run')
            ->everyMinute()
            ->withoutOverlapping()
            ->evenInMaintenanceMode()
            ->appendOutputTo(storage_path('logs/schedule.log'));
        // Sync UniFi sites periodically
        $schedule->command('unifi:sync-sites')
            ->everyMinute()
            ->withoutOverlapping()
            ->evenInMaintenanceMode()
            ->appendOutputTo(storage_path('logs/schedule.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('monitors:run')->everyMinute()->withoutOverlapping();
Schedule::command('unifi:sync-sites')->everyMinute()->withoutOverlapping();

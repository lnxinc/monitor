<?php

namespace App\Providers;

use App\Services\Monitoring\MonitorDriverManager;
use App\Services\Monitoring\MonitorStateService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MonitorDriverManager::class, function ($app) {
            return new MonitorDriverManager($app);
        });

        $this->app->singleton(MonitorStateService::class, function ($app) {
            return new MonitorStateService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

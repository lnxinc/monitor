<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\DeviceTypeController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\UnifiSiteManagerController;
use App\Http\Controllers\StatusPageController;
use App\Http\Controllers\NotificationChannelController;
use App\Http\Controllers\MonitorPolicyController;
use App\Http\Controllers\MaintenanceWindowController;
use App\Http\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('organizations', OrganizationController::class);
    Route::resource('devices', DeviceController::class);
    Route::resource('device-types', DeviceTypeController::class)->except(['show']);
    Route::resource('monitors', MonitorController::class);
    Route::post('monitors/{monitor}/run', [MonitorController::class, 'run'])->name('monitors.run');

    // Alerts & Policies
    Route::resource('channels', NotificationChannelController::class)->parameters(['channels' => 'channel']);
    Route::resource('policies', MonitorPolicyController::class)->parameters(['policies' => 'policy']);

    // Maintenance windows
    Route::resource('maintenance', MaintenanceWindowController::class)->parameters(['maintenance' => 'maintenance'])->except(['show']);

    // UniFi Site Manager integration
    Route::get('integrations/unifi', [UnifiSiteManagerController::class, 'index'])->name('integrations.unifi.index');
    Route::post('integrations/unifi/sync', [UnifiSiteManagerController::class, 'sync'])->name('integrations.unifi.sync');

    Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::post('incidents', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::patch('incidents/{incident}/acknowledge', [IncidentController::class, 'acknowledge'])->name('incidents.acknowledge');
    Route::patch('incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');

    // Internal docs: Device incidents webhook
    Route::get('docs/webhooks/incidents', function () {
        return Inertia::render('Docs/WebhooksIncidents');
    })->name('docs.webhooks.incidents');

    Route::get('docs/webhooks/monitors', function () {
        return Inertia::render('Docs/WebhooksMonitors');
    })->name('docs.webhooks.monitors');

    // Dashboard metrics (authenticated)
    Route::get('incidents/metrics/open-count', function () {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'open_count' => \App\Models\Incident::open()->count(),
        ]);
    })->name('incidents.metrics.open_count');
});

// Public Status Page
Route::get('/status', [StatusPageController::class, 'index'])->name('status.index');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

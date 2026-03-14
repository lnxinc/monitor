<?php

use App\Http\Controllers\Api\MonitorController as ApiMonitorController;
use App\Http\Controllers\DeviceWebhookController;
use App\Http\Controllers\MonitorWebhookController;
use Illuminate\Support\Facades\Route;

// Public endpoint for devices to report incidents using their secret
Route::post('/devices/{secret}/incidents', [DeviceWebhookController::class, 'reportIncident'])
    ->middleware('throttle:60,1')
    ->name('api.devices.incidents.store');

Route::post('/webhooks/monitors/{token}', [MonitorWebhookController::class, 'ingest'])
    ->middleware('throttle:60,1')
    ->name('api.monitors.webhooks.ingest');

Route::apiResource('monitors', ApiMonitorController::class)
    ->middleware('auth')
    ->names('api.monitors');

<?php

use App\Http\Controllers\DeviceWebhookController;
use Illuminate\Support\Facades\Route;

// Public endpoint for devices to report incidents using their secret
Route::post('/devices/{secret}/incidents', [DeviceWebhookController::class, 'reportIncident'])
    ->name('api.devices.incidents.store');


<?php

use App\Models\Monitor;
use App\Support\Monitoring\MonitorInputSanitizer;

it('sanitizes http monitor payloads', function () {
    $payload = [
        'name' => '  API Endpoint  ',
        'type' => 'http',
        'url' => ' https://example.com/health ',
        'frequency_minutes' => '5',
        'config' => [
            'timeout' => ' 30 ',
            'method' => 'get',
            'expected_status' => '200',
        ],
    ];

    $attributes = MonitorInputSanitizer::buildAttributes($payload);

    expect($attributes)->toMatchArray([
        'name' => 'API Endpoint',
        'type' => 'http',
        'url' => 'https://example.com/health',
        'frequency_minutes' => 5,
    ]);

    expect($attributes['config'])->toMatchArray([
        'target' => 'https://example.com/health',
        'timeout' => 30,
        'method' => 'GET',
        'expected_status' => 200,
    ]);
});

it('normalizes tcp targets with host and port', function () {
    $payload = [
        'name' => 'core switch',
        'type' => 'tcp',
        'url' => 'switch.local',
        'frequency_minutes' => 1,
        'config' => [
            'host' => ' switch.local ',
            'port' => ' 443 ',
            'timeout' => '10',
        ],
    ];

    $attributes = MonitorInputSanitizer::buildAttributes($payload);

    expect($attributes['config'])->toMatchArray([
        'host' => 'switch.local',
        'port' => 443,
        'timeout' => 10,
        'target' => 'switch.local:443',
    ]);
});

it('preserves existing frequency when omitted during update', function () {
    $monitor = new Monitor;
    $monitor->frequency_minutes = 15;
    $monitor->type = 'ping';
    $monitor->url = '10.0.0.1';
    $monitor->config = ['target' => '10.0.0.1', 'host' => '10.0.0.1'];

    $payload = [
        'name' => 'Gateway',
        'type' => 'ping',
        'url' => '10.0.0.1',
    ];

    $attributes = MonitorInputSanitizer::buildAttributes($payload, $monitor);

    expect($attributes['frequency_minutes'])->toBe(15);
    expect($attributes['config'])->toMatchArray([
        'target' => '10.0.0.1',
        'host' => '10.0.0.1',
    ]);
});

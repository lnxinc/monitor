<?php

use App\Models\Monitor;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('requires authentication for api monitor routes', function () {
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/monitors')->assertUnauthorized();
    $this->postJson('/api/monitors', [])->assertUnauthorized();
});

it('lists monitors with the expected contract shape', function () {
    Monitor::factory()->count(3)->create();

    $response = $this->getJson('/api/monitors');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [[
                'id',
                'name',
                'type',
                'url',
                'display_target',
                'config',
                'frequency_minutes',
                'status',
                'last_status_code',
                'last_response_time_ms',
                'last_checked_at',
                'failure_reason',
                'consecutive_failures',
                'webhook_token',
                'created_at',
                'updated_at',
            ]],
            'links',
            'meta',
        ]);

    expect($response->json('data.0.config.target'))->not()->toBeNull();
    expect($response->json('data.0.display_target'))->toEqual($response->json('data.0.config.target'));
});

it('creates an http monitor and normalizes config', function () {
    $payload = [
        'name' => 'Primary API',
        'type' => 'http',
        'url' => 'https://status.test/ping',
        'frequency_minutes' => 2,
        'config' => [
            'timeout' => 10,
            'method' => 'head',
            'expected_status' => 204,
        ],
    ];

    $response = $this->postJson('/api/monitors', $payload);

    $response->assertCreated();

    $response->assertJsonPath('data.url', 'https://status.test/ping');
    $response->assertJsonPath('data.config.method', 'HEAD');
    $response->assertJsonPath('data.config.target', 'https://status.test/ping');

    $this->assertDatabaseHas('monitors', [
        'name' => 'Primary API',
        'type' => 'http',
        'frequency_minutes' => 2,
    ]);
});

it('creates a sip monitor with phone_number config', function () {
    $payload = [
        'name' => 'SIP Test',
        'type' => 'sip',
        'url' => 'sip.example.com',
        'frequency_minutes' => 5,
        'config' => [
            'phone_number' => '+15551234567',
            'port' => 5060,
        ],
    ];

    $response = $this->postJson('/api/monitors', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.type', 'sip');

    $this->assertDatabaseHas('monitors', [
        'name' => 'SIP Test',
        'type' => 'sip',
    ]);
});

it('rejects sip monitor without phone_number', function () {
    $payload = [
        'name' => 'SIP Missing Phone',
        'type' => 'sip',
        'url' => 'sip.example.com',
        'frequency_minutes' => 5,
        'config' => [
            'port' => 5060,
        ],
    ];

    $response = $this->postJson('/api/monitors', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['config.phone_number']);
});

it('updates an existing monitor', function () {
    $monitor = Monitor::factory()->create([
        'type' => 'ping',
        'url' => '10.0.0.1',
        'config' => ['target' => '10.0.0.1', 'host' => '10.0.0.1'],
        'frequency_minutes' => 5,
    ]);

    $payload = [
        'name' => 'Gateway Check',
        'type' => 'tcp',
        'url' => 'gateway.internal',
        'frequency_minutes' => 3,
        'config' => [
            'host' => 'gateway.internal',
            'port' => 8443,
            'timeout' => 15,
        ],
    ];

    $response = $this->putJson("/api/monitors/{$monitor->id}", $payload);

    $response->assertOk();
    $response->assertJsonPath('data.type', 'tcp');
    $response->assertJsonPath('data.config.target', 'gateway.internal:8443');

    $this->assertDatabaseHas('monitors', [
        'id' => $monitor->id,
        'type' => 'tcp',
        'frequency_minutes' => 3,
    ]);
});

it('deletes a monitor', function () {
    $monitor = Monitor::factory()->create();

    $response = $this->deleteJson("/api/monitors/{$monitor->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('monitors', ['id' => $monitor->id]);
});

it('rejects invalid payloads with validation errors', function () {
    $payload = [
        'name' => '',
        'type' => 'http',
        'url' => 'not-a-url',
        'frequency_minutes' => 0,
    ];

    $response = $this->postJson('/api/monitors', $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['name', 'url', 'frequency_minutes']);
});

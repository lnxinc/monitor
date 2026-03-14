<?php

use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('requires email config for email channel type', function () {
    $response = $this->post(route('channels.store'), [
        'name' => 'Test Email',
        'type' => 'email',
        'config' => [],
    ]);

    $response->assertSessionHasErrors('config.email');
});

it('requires webhook_url config for slack_webhook channel type', function () {
    $response = $this->post(route('channels.store'), [
        'name' => 'Test Slack',
        'type' => 'slack_webhook',
        'config' => [],
    ]);

    $response->assertSessionHasErrors('config.webhook_url');
});

it('requires url config for webhook channel type', function () {
    $response = $this->post(route('channels.store'), [
        'name' => 'Test Webhook',
        'type' => 'webhook',
        'config' => [],
    ]);

    $response->assertSessionHasErrors('config.url');
});

it('creates email channel with valid config', function () {
    $response = $this->post(route('channels.store'), [
        'name' => 'Ops Email',
        'type' => 'email',
        'config' => ['email' => 'ops@example.com'],
    ]);

    $response->assertRedirect(route('channels.index'));
    $this->assertDatabaseHas('notification_channels', [
        'name' => 'Ops Email',
        'type' => 'email',
    ]);
});

it('creates slack_webhook channel with valid config', function () {
    $response = $this->post(route('channels.store'), [
        'name' => 'NOC Slack',
        'type' => 'slack_webhook',
        'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
    ]);

    $response->assertRedirect(route('channels.index'));
    $this->assertDatabaseHas('notification_channels', [
        'name' => 'NOC Slack',
        'type' => 'slack_webhook',
    ]);
});

it('rejects invalid channel type', function () {
    $response = $this->post(route('channels.store'), [
        'name' => 'Bad Type',
        'type' => 'sms',
        'config' => [],
    ]);

    $response->assertSessionHasErrors('type');
});

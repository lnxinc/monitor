<?php

use App\Jobs\SendNotification;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

it('sends slack notification via job', function () {
    Http::fake();

    $monitor = Monitor::factory()->create(['status' => 'down']);
    $channel = NotificationChannel::create([
        'name' => 'Slack',
        'type' => 'slack_webhook',
        'config' => ['webhook_url' => 'https://hooks.slack.test/test'],
        'is_active' => true,
    ]);

    $job = new SendNotification($channel, $monitor, 'down', ['error' => 'timeout']);
    $job->handle();

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://hooks.slack.test/test');
});

it('sends email notification via job', function () {
    $sent = false;
    Mail::shouldReceive('raw')
        ->once()
        ->andReturnUsing(function ($body, $callback) use (&$sent) {
            $message = new class
            {
                public string $toAddr = '';

                public string $subj = '';

                public function to($addr): self
                {
                    $this->toAddr = $addr;

                    return $this;
                }

                public function subject($s): self
                {
                    $this->subj = $s;

                    return $this;
                }
            };
            $callback($message);
            expect($message->toAddr)->toBe('ops@example.com');
            expect($message->subj)->toContain('DOWN');
            $sent = true;
        });

    $monitor = Monitor::factory()->create(['status' => 'down']);
    $channel = NotificationChannel::create([
        'name' => 'Email',
        'type' => 'email',
        'config' => ['email' => 'ops@example.com'],
        'is_active' => true,
    ]);

    $job = new SendNotification($channel, $monitor, 'down', ['error' => 'timeout']);
    $job->handle();

    expect($sent)->toBeTrue();
});

it('sends webhook notification via job', function () {
    Http::fake();

    $monitor = Monitor::factory()->create(['status' => 'down']);
    $channel = NotificationChannel::create([
        'name' => 'Webhook',
        'type' => 'slack_webhook',
        'config' => [
            'webhook_url' => 'https://hooks.test/generic',
            'format' => 'generic',
        ],
        'is_active' => true,
    ]);

    $job = new SendNotification($channel, $monitor, 'down', ['error' => 'timeout']);
    $job->handle();

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['event'] === 'down' && $request['monitor']['id'] === $monitor->id);
});

it('skips email when no email configured', function () {
    Mail::fake();

    $monitor = Monitor::factory()->create(['status' => 'down']);
    $channel = NotificationChannel::create([
        'name' => 'Empty Email',
        'type' => 'email',
        'config' => [],
        'is_active' => true,
    ]);

    $job = new SendNotification($channel, $monitor, 'down');
    $job->handle();

    Mail::assertNothingSent();
});

it('has retry configuration', function () {
    $monitor = Monitor::factory()->create();
    $channel = NotificationChannel::create([
        'name' => 'Test',
        'type' => 'email',
        'config' => ['email' => 'test@example.com'],
        'is_active' => true,
    ]);

    $job = new SendNotification($channel, $monitor, 'down');

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe([10, 30, 60]);
});

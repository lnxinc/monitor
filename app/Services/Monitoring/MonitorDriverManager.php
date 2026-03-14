<?php

namespace App\Services\Monitoring;

use Illuminate\Contracts\Container\Container;

class MonitorDriverManager
{
    /** @var array<string, class-string<MonitorDriver>> */
    protected array $map = [
        'http' => \App\Services\Monitoring\Drivers\HttpMonitorDriver::class,
        'ping' => \App\Services\Monitoring\Drivers\PingMonitorDriver::class,
        'tcp' => \App\Services\Monitoring\Drivers\TcpMonitorDriver::class,
        'ssl' => \App\Services\Monitoring\Drivers\SslCertificateMonitorDriver::class,
        'webhook' => \App\Services\Monitoring\Drivers\WebhookMonitorDriver::class,
        'sip' => \App\Services\Monitoring\Drivers\SipMonitorDriver::class,
    ];

    /** @var array<string, MonitorDriver> */
    protected array $drivers = [];

    public function __construct(protected Container $container) {}

    public function register(string $type, string $driverClass): void
    {
        $this->map[$type] = $driverClass;
        unset($this->drivers[$type]);
    }

    public function driver(?string $type): ?MonitorDriver
    {
        if (! $type) {
            return null;
        }

        if (! isset($this->drivers[$type])) {
            $class = $this->map[$type] ?? null;
            if (! $class) {
                return null;
            }
            $this->drivers[$type] = $this->container->make($class);
        }

        return $this->drivers[$type];
    }
}

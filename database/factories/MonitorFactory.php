<?php

namespace Database\Factories;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    protected $model = Monitor::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['http', 'ping', 'tcp']);
        $url = match ($type) {
            'ping' => $this->faker->ipv4(),
            'tcp' => $this->faker->domainName(),
            default => $this->faker->url(),
        };

        $config = match ($type) {
            'ping' => ['target' => $url, 'host' => $url, 'timeout' => 5, 'count' => 4],
            'tcp' => ['target' => $url.':443', 'host' => $url, 'port' => 443, 'timeout' => 5],
            default => ['target' => $url, 'method' => 'HEAD', 'timeout' => 15],
        };

        return [
            'name' => $this->faker->sentence(3),
            'type' => $type,
            'url' => $url,
            'config' => $config,
            'frequency_minutes' => $this->faker->randomElement([1, 5, 10, 15]),
            'status' => 'unknown',
            'webhook_token' => $type === 'webhook' ? Str::uuid()->toString() : null,
        ];
    }

    public function webhook(): self
    {
        return $this->state(function () {
            $token = (string) Str::uuid();
            return [
                'type' => 'webhook',
                'url' => 'webhook://'.$token,
                'config' => ['target' => $token],
                'webhook_token' => $token,
                'frequency_minutes' => 5,
            ];
        });
    }
}

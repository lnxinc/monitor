<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => \App\Models\Organization::factory(),
            'name' => fake()->randomElement([
                'Web Server',
                'Database Server',
                'Load Balancer',
                'Application Server',
                'Cache Server',
                'File Server',
                'Mail Server',
                'DNS Server',
                'Firewall',
                'Switch',
                'Router',
                'NAS Storage'
            ]) . ' - ' . fake()->randomNumber(2),
            'ip_address' => fake()->localIpv4(),
            'secret' => \Illuminate\Support\Str::random(32),
            'status' => fake()->randomElement(['online', 'offline', 'warning', 'critical']),
            'last_seen_at' => fake()->optional(0.8)->dateTimeBetween('-1 day', 'now'),
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
            'last_seen_at' => fake()->dateTimeBetween('-7 days', '-1 hour'),
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'critical',
            'last_seen_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $severity = fake()->randomElement(['low', 'medium', 'high', 'critical']);
        $status = fake()->randomElement(['open', 'acknowledged', 'resolved']);
        $occurredAt = fake()->dateTimeBetween('-30 days', 'now');

        $acknowledgedAt = null;
        $resolvedAt = null;

        if ($status === 'acknowledged' || $status === 'resolved') {
            $acknowledgedAt = fake()->dateTimeBetween($occurredAt, 'now');
        }

        if ($status === 'resolved') {
            $resolvedAt = fake()->dateTimeBetween($acknowledgedAt ?: $occurredAt, 'now');
        }

        return [
            'device_id' => \App\Models\Device::factory(),
            'severity' => $severity,
            'title' => fake()->randomElement([
                'High CPU Usage Detected',
                'Memory Usage Critical',
                'Disk Space Low',
                'Network Connectivity Issues',
                'Service Unavailable',
                'Database Connection Failed',
                'SSL Certificate Expired',
                'Backup Process Failed',
                'Security Breach Detected',
                'Hardware Failure',
                'Temperature Alert',
                'Power Supply Warning',
                'Unauthorized Access Attempt',
                'Performance Degradation',
                'Application Error Rate High'
            ]),
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => $status,
            'occurred_at' => $occurredAt,
            'acknowledged_at' => $acknowledgedAt,
            'resolved_at' => $resolvedAt,
            'metadata' => fake()->optional(0.3)->passthrough([
                'cpu_usage' => fake()->numberBetween(0, 100),
                'memory_usage' => fake()->numberBetween(0, 100),
                'disk_usage' => fake()->numberBetween(0, 100),
                'error_code' => fake()->numberBetween(400, 599),
            ]),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'acknowledged_at' => null,
            'resolved_at' => null,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
            'title' => 'Critical System Alert',
        ]);
    }
}

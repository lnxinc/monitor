<?php

namespace App\Support\Monitoring;

use App\Models\Monitor;
use Illuminate\Support\Arr;

class MonitorInputSanitizer
{
    /**
     * Build normalized monitor attributes from validated request data.
     */
    public static function buildAttributes(array $data, ?Monitor $existing = null): array
    {
        $type = $data['type'];
        $target = trim($data['url']);

        $config = self::normalizeConfig($data['config'] ?? [], $type, $target);

        $attributes = [
            'name' => trim($data['name']),
            'type' => $type,
            'url' => $target,
            'config' => $config,
        ];

        $frequency = $data['frequency_minutes'] ?? $existing?->frequency_minutes;
        if (! is_null($frequency) && $type !== 'webhook') {
            $attributes['frequency_minutes'] = (int) $frequency;
        } elseif ($type === 'webhook') {
            $attributes['frequency_minutes'] = null;
        }

        return $attributes;
    }

    private static function normalizeConfig(array $config, string $type, string $target): array
    {
        $config = Arr::only($config, ['host', 'port', 'timeout', 'count', 'method', 'expected_status']);

        $config = array_map(function ($value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            return $value;
        }, $config);

        if (isset($config['port'])) {
            $config['port'] = is_numeric($config['port']) ? (int) $config['port'] : null;
        }
        if (isset($config['timeout'])) {
            $config['timeout'] = is_numeric($config['timeout']) ? (int) $config['timeout'] : null;
        }
        if (isset($config['count'])) {
            $config['count'] = is_numeric($config['count']) ? (int) $config['count'] : null;
        }
        if (isset($config['expected_status'])) {
            $config['expected_status'] = is_numeric($config['expected_status']) ? (int) $config['expected_status'] : null;
        }
        if (isset($config['method']) && is_string($config['method'])) {
            $config['method'] = strtoupper($config['method']);
        }

        $config['target'] = $target;

        if ($type === 'ping') {
            $config['host'] = $config['host'] ?? $target;
        }

        if ($type === 'tcp') {
            $config['host'] = $config['host'] ?? $target;
            if (isset($config['host'], $config['port'])) {
                $config['target'] = sprintf('%s:%s', $config['host'], $config['port']);
            }
        }

        if ($type === 'ssl') {
            $config['host'] = $config['host'] ?? $target;
            $config['port'] = $config['port'] ?? 443;
            if (isset($config['host'], $config['port'])) {
                $config['target'] = sprintf('%s:%s', $config['host'], $config['port']);
            }
        }

        return array_filter(
            $config,
            fn ($value) => ! is_null($value) && $value !== ''
        );
    }
}

<?php

namespace App\Services\Monitoring\Drivers;

use App\Models\Monitor;
use App\Services\Monitoring\MonitorDriver;
use App\Services\Monitoring\MonitorResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class SslCertificateMonitorDriver implements MonitorDriver
{
    public function check(Monitor $monitor): MonitorResult
    {
        $host = $monitor->config['host'] ?? $monitor->url;
        $port = (int) Arr::get($monitor->config, 'port', 443);

        if (! $host) {
            return MonitorResult::failure('Missing host');
        }

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $start = microtime(true);
        $client = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        $duration = (int) round((microtime(true) - $start) * 1000);

        if (! $client) {
            $meta = ['host' => $host, 'port' => $port, 'errno' => $errno];
            return MonitorResult::failure($errstr ?: 'Unable to negotiate SSL', $meta, null, $duration);
        }

        $params = stream_context_get_params($client);
        fclose($client);

        if (! isset($params['options']['ssl']['peer_certificate'])) {
            return MonitorResult::failure('Certificate not available', ['host' => $host, 'port' => $port], null, $duration);
        }

        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        if (! is_array($cert)) {
            return MonitorResult::failure('Unable to parse certificate', ['host' => $host, 'port' => $port], null, $duration);
        }

        $validFrom = isset($cert['validFrom_time_t']) ? Carbon::createFromTimestamp($cert['validFrom_time_t']) : null;
        $validTo = isset($cert['validTo_time_t']) ? Carbon::createFromTimestamp($cert['validTo_time_t']) : null;
        $issuer = null;

        if (isset($cert['issuer']) && is_array($cert['issuer'])) {
            $issuer = collect($cert['issuer'])
                ->map(fn ($value, $key) => "{$key}={$value}")
                ->implode(', ');
        }

        $meta = [
            'subject' => $cert['name'] ?? null,
            'valid_from' => $validFrom?->toIso8601String(),
            'valid_to' => $validTo?->toIso8601String(),
            'serial_number' => $cert['serialNumberHex'] ?? null,
        ];

        if ($validTo) {
            $meta['days_until_expiry'] = $validTo->isPast()
                ? -$validTo->diffInDays(now())
                : now()->diffInDays($validTo);
        }

        $result = MonitorResult::success($meta, null, $duration);
        return $result->withSsl($validFrom, $validTo, $issuer);
    }
}

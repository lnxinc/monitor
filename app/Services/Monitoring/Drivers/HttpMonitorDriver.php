<?php

namespace App\Services\Monitoring\Drivers;

use App\Models\Monitor;
use App\Services\Monitoring\MonitorDriver;
use App\Services\Monitoring\MonitorResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class HttpMonitorDriver implements MonitorDriver
{
    public function check(Monitor $monitor): MonitorResult
    {
        $url = $monitor->url;
        if (! $url) {
            return MonitorResult::failure('Missing URL');
        }

        $ch = curl_init($url);
        $method = strtoupper(Arr::get($monitor->config, 'method', 'HEAD'));
        if (! in_array($method, ['HEAD', 'GET'], true)) {
            $method = 'HEAD';
        }

        $headers = [
            'User-Agent: '.config('monitoring.http.user_agent', 'MSP-Monitor/2.0'),
        ];

        $options = [
            CURLOPT_NOBODY => $method === 'HEAD',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => Arr::get($monitor->config, 'timeout', 15),
            CURLOPT_CONNECTTIMEOUT => Arr::get($monitor->config, 'connect_timeout', 10),
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $headers,
        ];

        $options[CURLOPT_CUSTOMREQUEST] = $method;

        curl_setopt_array($ch, $options);

        $start = microtime(true);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: null;
        $totalTime = (int) round((curl_getinfo($ch, CURLINFO_TOTAL_TIME) ?: microtime(true) - $start) * 1000);
        $error = $response === false ? curl_error($ch) : null;

        $meta = [
            'content_type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: null,
            'primary_ip' => curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?: null,
            'redirect_count' => curl_getinfo($ch, CURLINFO_REDIRECT_COUNT) ?: 0,
        ];

        curl_close($ch);

        $status = 'down';
        if ($error === null && $statusCode !== null) {
            $expected = Arr::get($monitor->config, 'expected_status');
            if ($expected) {
                $status = ((int) $statusCode === (int) $expected) ? 'up' : 'down';
            } else {
                $status = ($statusCode >= 200 && $statusCode < 400) ? 'up' : 'down';
            }
        } elseif ($error === null && $response !== false) {
            $status = 'up';
        }

        $result = new MonitorResult($status, $statusCode, $totalTime, $error, array_filter($meta));

        if (Str::startsWith(strtolower($url), 'https://')) {
            $ssl = $this->fetchSslMetadata($url);
            if ($ssl) {
                [$validFrom, $expiresAt, $issuer] = $ssl;
                $result = $result->withSsl($validFrom, $expiresAt, $issuer);
            }
        }

        return $result;
    }

    private function fetchSslMetadata(string $url): ?array
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? null;
        if (! $host) {
            return null;
        }

        $port = $parts['port'] ?? 443;
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (! $client) {
            return null;
        }

        $params = stream_context_get_params($client);
        @fclose($client);

        if (! isset($params['options']['ssl']['peer_certificate'])) {
            return null;
        }

        $cert = @openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        if (! is_array($cert)) {
            return null;
        }

        $validFrom = isset($cert['validFrom_time_t']) ? Carbon::createFromTimestamp($cert['validFrom_time_t']) : null;
        $validTo = isset($cert['validTo_time_t']) ? Carbon::createFromTimestamp($cert['validTo_time_t']) : null;
        $issuer = null;

        if (isset($cert['issuer']) && is_array($cert['issuer'])) {
            $issuer = collect($cert['issuer'])
                ->map(fn ($value, $key) => "{$key}={$value}")
                ->implode(', ');
        }

        return [$validFrom, $validTo, $issuer];
    }
}

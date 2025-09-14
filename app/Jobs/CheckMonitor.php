<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CheckMonitor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Monitor $monitor) {}

    public function handle(): void
    {
        $monitor = $this->monitor->fresh();
        if (! $monitor) return;

        $url = $monitor->url;
        $start = microtime(true);
        $status = 'down';
        $statusCode = null;
        $error = null;
        $responseTime = null;
        $sslData = [ 'ssl_valid_from' => null, 'ssl_expires_at' => null, 'ssl_issuer' => null ];

        try {
            $isHttps = Str::startsWith(strtolower($url), 'https://');
            $context = null;
            if ($isHttps) {
                $context = stream_context_create([
                    'ssl' => [
                        'capture_peer_cert' => true,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
            }
            $method = 'HEAD';
            $opts = [
                'http' => [
                    'method' => $method,
                    'timeout' => 10,
                    'ignore_errors' => true,
                    'header' => "User-Agent: Monitor/1.0\r\n",
                ],
            ];
            if ($context) {
                $context = stream_context_create(array_replace_recursive($opts, stream_context_get_options($context)));
            } else {
                $context = stream_context_create($opts);
            }

            $fp = @fopen($url, 'r', false, $context);
            if (! $fp) {
                $opts['http']['method'] = 'GET';
                $context = stream_context_create($opts);
                $fp = @fopen($url, 'r', false, $context);
            }

            $responseTime = (int) round((microtime(true) - $start) * 1000);

            if ($fp) {
                $meta = stream_get_meta_data($fp);
                @fclose($fp);

                if (!empty($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
                    foreach ($meta['wrapper_data'] as $h) {
                        if (preg_match('#^HTTP/[^\s]+\s+(\d{3})#i', $h, $m)) {
                            $statusCode = (int) $m[1];
                            break;
                        }
                    }
                }

                $status = ($statusCode && $statusCode >= 200 && $statusCode < 400) ? 'up' : 'down';

                if ($isHttps) {
                    $parts = parse_url($url);
                    $host = $parts['host'] ?? null;
                    $port = $parts['port'] ?? 443;
                    if ($host) {
                        $ctx = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false]]);
                        $client = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
                        if ($client) {
                            $params = stream_context_get_params($client);
                            @fclose($client);
                            if (isset($params['options']['ssl']['peer_certificate'])) {
                                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                                if ($cert) {
                                    if (isset($cert['validFrom_time_t'])) $sslData['ssl_valid_from'] = date('c', $cert['validFrom_time_t']);
                                    if (isset($cert['validTo_time_t'])) $sslData['ssl_expires_at'] = date('c', $cert['validTo_time_t']);
                                    if (isset($cert['issuer']) && is_array($cert['issuer'])) {
                                        $sslData['ssl_issuer'] = implode(', ', array_map(fn($k,$v)=>"$k=$v", array_keys($cert['issuer']), $cert['issuer']));
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $error = 'Connection failed';
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $responseTime = (int) round((microtime(true) - $start) * 1000);
        }

        MonitorCheck::create([
            'monitor_id' => $monitor->id,
            'status' => $status === 'up' ? 'up' : 'down',
            'status_code' => $statusCode,
            'response_time_ms' => $responseTime,
            'error' => $error,
            'checked_at' => now(),
        ]);

        $fields = [
            'status' => $status,
            'last_status_code' => $statusCode,
            'last_response_time_ms' => $responseTime,
            'last_checked_at' => now(),
            'failure_reason' => $error,
        ];
        $fields = array_merge($fields, array_filter($sslData, fn($v) => !is_null($v)));

        if ($status === 'down') {
            $fields['consecutive_failures'] = ($monitor->consecutive_failures ?? 0) + 1;
        } else {
            $fields['consecutive_failures'] = 0;
        }

        $monitor->update($fields);
    }
}


<?php

namespace App\Services\Monitoring\Drivers;

use App\Models\Monitor;
use App\Services\Monitoring\MonitorDriver;
use App\Services\Monitoring\MonitorResult;
use Illuminate\Support\Arr;

class SipMonitorDriver implements MonitorDriver
{
    public function check(Monitor $monitor): MonitorResult
    {
        $host = Arr::get($monitor->config, 'host', $monitor->url);
        $port = (int) Arr::get($monitor->config, 'port', 5060);
        $phoneNumber = Arr::get($monitor->config, 'phone_number');
        $timeout = (int) Arr::get($monitor->config, 'timeout', 10);

        if (! $host || $port < 1 || $port > 65535) {
            return MonitorResult::failure('Invalid host or port');
        }

        if (! $phoneNumber) {
            return MonitorResult::failure('Phone number is required');
        }

        $start = microtime(true);
        $result = $this->sendSipOptions($host, $port, $phoneNumber, $timeout);
        $duration = (int) round((microtime(true) - $start) * 1000);

        if (! $result['success']) {
            $meta = [
                'host' => $host,
                'port' => $port,
                'phone_number' => $phoneNumber,
            ];

            return MonitorResult::failure($result['error'], $meta, null, $duration);
        }

        $meta = [
            'host' => $host,
            'port' => $port,
            'phone_number' => $phoneNumber,
            'sip_response' => $result['response'] ?? null,
        ];

        return MonitorResult::success($meta, $result['status_code'] ?? null, $duration);
    }

    protected function sendSipOptions(string $host, int $port, string $phoneNumber, int $timeout): array
    {
        $errno = 0;
        $errstr = null;
        $connection = @fsockopen($host, $port, $errno, $errstr, max(1, $timeout));

        if (! $connection) {
            return [
                'success' => false,
                'error' => $errstr ?: 'Connection failed',
            ];
        }

        stream_set_timeout($connection, $timeout);

        $callId = bin2hex(random_bytes(16));
        $branch = 'z9hG4bK'.bin2hex(random_bytes(8));
        $tag = bin2hex(random_bytes(8));

        $sipRequest = "OPTIONS sip:{$phoneNumber}@{$host} SIP/2.0\r\n";
        $sipRequest .= "Via: SIP/2.0/UDP {$host}:{$port};branch={$branch}\r\n";
        $sipRequest .= "From: <sip:monitor@{$host}>;tag={$tag}\r\n";
        $sipRequest .= "To: <sip:{$phoneNumber}@{$host}>\r\n";
        $sipRequest .= "Call-ID: {$callId}@{$host}\r\n";
        $sipRequest .= "CSeq: 1 OPTIONS\r\n";
        $sipRequest .= "Contact: <sip:monitor@{$host}:{$port}>\r\n";
        $sipRequest .= "Max-Forwards: 70\r\n";
        $sipRequest .= "User-Agent: Monitor/1.0\r\n";
        $sipRequest .= "Accept: application/sdp\r\n";
        $sipRequest .= "Content-Length: 0\r\n\r\n";

        fwrite($connection, $sipRequest);

        $response = '';
        $startTime = microtime(true);
        while (! feof($connection) && (microtime(true) - $startTime) < $timeout) {
            $line = fgets($connection, 1024);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (trim($line) === '' && str_contains($response, 'SIP/2.0')) {
                break;
            }
        }

        fclose($connection);

        if (empty($response)) {
            return [
                'success' => false,
                'error' => 'No response from SIP server',
            ];
        }

        if (preg_match('/^SIP\/2\.0 (\d+)/', $response, $matches)) {
            $statusCode = (int) $matches[1];
            $isSuccess = $statusCode >= 200 && $statusCode < 300;

            return [
                'success' => $isSuccess,
                'error' => $isSuccess ? null : "SIP server returned status {$statusCode}",
                'status_code' => $statusCode,
                'response' => trim(substr($response, 0, 500)),
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid SIP response',
        ];
    }
}

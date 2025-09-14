<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class UnifiSiteManager
{
    public function hasApiKey(): bool
    {
        return (bool) config('unifi.api_key');
    }

    public function fetchSites(): array
    {
        $apiKey = config('unifi.api_key');
        $base = rtrim(config('unifi.base_url'), '/');
        if (! $apiKey) {
            return [];
        }

        $response = Http::timeout(15)
            ->withHeaders([
                'X-API-KEY' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->get("{$base}/hosts");

        if (! $response->ok()) {
            return [];
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return [];
        }

        // Locate the list of hosts within common wrappers
        $items = [];
        if (isset($payload['data']) && is_array($payload['data'])) {
            $items = $payload['data'];
        } elseif (isset($payload['hosts']) && is_array($payload['hosts'])) {
            $items = $payload['hosts'];
        } else {
            // If all values are arrays, treat as list; otherwise find the first array-of-arrays
            $allArrays = !empty($payload);
            foreach ($payload as $v) { if (!is_array($v)) { $allArrays = false; break; } }
            if ($allArrays) {
                $items = $payload;
            } else {
                foreach ($payload as $v) {
                    if (is_array($v)) {
                        $isList = true;
                        foreach ($v as $vv) { if (!is_array($vv)) { $isList = false; break; } }
                        if ($isList) { $items = $v; break; }
                    }
                }
            }
        }

        // Ensure only array items are normalized
        $items = array_values(array_filter($items, fn ($v) => is_array($v)));

        return array_map(fn (array $raw) => $this->normalize($raw), $items);
    }

    /**
     * Sync fetched sites into the local database: Organizations, Devices, and Incidents.
     * Returns an array summary: [total, created, updated, incidents].
     */
    public function syncIntoDatabase(): array
    {
        $sites = $this->fetchSites();

        $created = 0; $updated = 0; $incidents = 0;

        // Lazy-load models to avoid circular deps in method signature
        $deviceType = \App\Models\DeviceType::firstOrCreate(
            ['name' => 'UniFi Device'],
            ['description' => 'Devices managed via UniFi Site Manager', 'icon' => 'wifi']
        );

        foreach ($sites as $site) {
            $org = \App\Models\Organization::firstOrCreate(
                ['name' => $site['organization_name']],
                ['description' => 'Imported from UniFi Site Manager']
            );

            $device = \App\Models\Device::where('external_source', 'unifi_site_manager')
                ->where('external_id', $site['external_id'])
                ->first();

            // Prefer UniFi's last connection change timestamp if available
            $lastSeenAt = now();
            $lastConnIso = $site['raw']['lastConnectionStateChange'] ?? null;
            if (is_string($lastConnIso)) {
                try { $lastSeenAt = \Carbon\Carbon::parse($lastConnIso); } catch (\Throwable $e) {}
            }

            $payload = [
                'organization_id' => $org->id,
                'device_type_id' => $deviceType->id,
                'name' => $site['name'],
                'ip_address' => $site['ip_address'] ?: '0.0.0.0',
                'status' => $site['is_online'] ? 'online' : 'offline',
                'last_seen_at' => $lastSeenAt,
                'external_source' => 'unifi_site_manager',
                'external_id' => $site['external_id'],
            ];

            if ($device) {
                $changes = [];
                foreach (['organization_id','device_type_id','name','ip_address','status'] as $k) {
                    if ($device->{$k} !== $payload[$k]) $changes[$k] = $payload[$k];
                }
                $changes['last_seen_at'] = $lastSeenAt;
                if (! empty($changes)) {
                    $device->update($changes);
                    $updated++;
                }
            } else {
                $device = \App\Models\Device::create($payload);
                $created++;
            }

            if (! $site['is_online'] || $site['has_internet_issue']) {
                $openExists = \App\Models\Incident::where('device_id', $device->id)
                    ->where('status', 'open')
                    ->exists();
                if (! $openExists) {
                    $title = ! $site['is_online'] ? 'UniFi site offline' : 'Internet issue detected';
                    $severity = ! $site['is_online'] ? 'high' : 'medium';
                    \App\Models\Incident::create([
                        'device_id' => $device->id,
                        'severity' => $severity,
                        'title' => $title,
                        'description' => 'Raised by UniFi Site Manager sync.',
                        'status' => 'open',
                        'occurred_at' => now(),
                        'metadata' => [ 'source' => 'unifi_site_manager', 'site_id' => $site['external_id'] ],
                    ]);
                    $incidents++;
                }
            }
        }

        return [
            'total' => count($sites),
            'created' => $created,
            'updated' => $updated,
            'incidents' => $incidents,
        ];
    }

    /**
     * Normalize Site Manager host/site payload to a consistent structure.
     * Attempts to be resilient to slight schema differences.
     */
    private function normalize(array $raw): array
    {
        $rs = Arr::get($raw, 'reportedState', []);

        // External ID: prefer stable hardware UUIDs, then MAC, then fallback to controller id
        $externalId = Arr::get($raw, 'hardwareId')
            ?? Arr::get($rs, 'hardware.uuid')
            ?? Arr::get($rs, 'mac')
            ?? Arr::get($raw, 'id')
            ?? Arr::get($raw, '_id')
            ?? Arr::get($raw, 'site_id')
            ?? sha1(json_encode($raw));

        // Hostname and names
        $hostname = $this->pickHostname($rs) ?? $this->pickHostname($raw);
        $name = $hostname ?: (Arr::get($rs, 'name') ?: (string) $externalId);

        // Online/offline detection tailored to Site Manager JSON
        $state = strtolower((string) (Arr::get($rs, 'state') ?? ''));
        $controllers = Arr::get($rs, 'controllers', []);
        $networkCtrl = null;
        if (is_array($controllers)) {
            foreach ($controllers as $ctrl) {
                if (is_array($ctrl) && strtolower((string)($ctrl['name'] ?? '')) === 'network') {
                    $networkCtrl = $ctrl; break;
                }
            }
        }
        $ncState = strtolower((string) ($networkCtrl['state'] ?? ''));
        $ncStatus = strtolower((string) ($networkCtrl['status'] ?? ''));
        $ncRunning = (bool) ($networkCtrl['isRunning'] ?? false);

        $isOnline = false;
        if ($state === 'connected') {
            $isOnline = true;
        } elseif ($networkCtrl) {
            $isOnline = in_array($ncState, ['active', 'ready'], true) && $ncRunning && $ncStatus !== 'offline';
        } else {
            // Fallback to userData.connectedState if available
            $connectedState = strtolower((string) (Arr::get($raw, 'userData.consoleGroupMembers.0.roleAttributes.connectedState') ?? ''));
            $isOnline = $connectedState === 'connected';
        }

        // Internet issue detection: be conservative and only flag when controller reports a problematic status
        $hasInternetIssue = false;
        if ($networkCtrl) {
            $problemStatuses = ['warning', 'degraded', 'offline', 'error', 'critical', 'down'];
            if (in_array($ncStatus, $problemStatuses, true)) {
                $hasInternetIssue = true;
            }
        }

        // IP address selection
        $ip = (string) (Arr::get($rs, 'ip') ?? '');
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $wans = Arr::get($rs, 'wans', []);
            if (is_array($wans)) {
                foreach ($wans as $wan) {
                    $cand = $wan['ipv4'] ?? null;
                    if (is_string($cand) && filter_var($cand, FILTER_VALIDATE_IP)) { $ip = $cand; break; }
                }
            }
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ipAddrs = Arr::get($rs, 'ipAddrs', []);
            if (is_array($ipAddrs)) {
                foreach ($ipAddrs as $cand) {
                    if (is_string($cand) && filter_var($cand, FILTER_VALIDATE_IP) && strpos($cand, ':') === false) { $ip = $cand; break; }
                }
            }
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }

        // Organization name must use hostname per requirement
        $orgName = $hostname ?: $name;

        return [
            'external_id' => (string) $externalId,
            'name' => (string) $name,
            'organization_name' => (string) $orgName,
            'ip_address' => filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0',
            'is_online' => (bool) $isOnline,
            'has_internet_issue' => (bool) $hasInternetIssue,
            'raw' => $raw,
        ];
    }

    private function pickHostname(array $raw): ?string
    {
        $candidates = [
            'reportedState.hostname', 'reportedState.name',
            'hostname', 'host_name', 'host', 'name',
            'device.hostname', 'device.name', 'system.hostname',
            'device_name', 'display_name',
        ];
        foreach ($candidates as $key) {
            $val = Arr::get($raw, $key);
            if (is_string($val)) {
                $val = trim($val);
                if ($val !== '' && ! $this->looksLikeControllerId($val)) {
                    return $val;
                }
            }
        }
        return null;
    }

    private function looksLikeControllerId(string $value): bool
    {
        $v = trim($value);
        if ($v === '') return false;
        if (strlen($v) > 50) return true;
        // Hex with optional colon + digits (e.g., ABCDEF...:123456)
        if (preg_match('/^[0-9A-F]{16,}(?::\d+)?$/i', $v)) return true;
        return false;
    }
}

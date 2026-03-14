<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Organization;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $deviceCounts = Device::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online")
            ->selectRaw("SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline")
            ->selectRaw("SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning")
            ->selectRaw("SUM(CASE WHEN status = 'critical' THEN 1 ELSE 0 END) as critical")
            ->first();

        $incidentCounts = Incident::open()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical")
            ->first();

        $phoneCounts = Monitor::where('type', 'sip')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up")
            ->selectRaw("SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) as down")
            ->first();

        $stats = [
            'total_organizations' => Organization::count(),
            'total_devices' => (int) $deviceCounts->total,
            'online_devices' => (int) $deviceCounts->online,
            'offline_devices' => (int) $deviceCounts->offline,
            'warning_devices' => (int) $deviceCounts->warning,
            'critical_devices' => (int) $deviceCounts->critical,
            'open_incidents' => (int) $incidentCounts->total,
            'critical_incidents' => (int) $incidentCounts->critical,
            'total_phone_monitors' => (int) $phoneCounts->total,
            'phone_monitors_up' => (int) $phoneCounts->up,
            'phone_monitors_down' => (int) $phoneCounts->down,
        ];

        $recentIncidents = Incident::with(['device.organization'])
            ->orderBy('occurred_at', 'desc')
            ->limit(10)
            ->get();

        $devicesByStatus = Device::query()
            ->toBase()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $incidentsBySeverity = Incident::open()
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->get()
            ->pluck('count', 'severity')
            ->toArray();

        $phoneMonitors = Monitor::where('type', 'sip')
            ->orderBy('last_checked_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($monitor) {
                return [
                    'id' => $monitor->id,
                    'name' => $monitor->name,
                    'phone_number' => $monitor->config['phone_number'] ?? 'N/A',
                    'status' => $monitor->status,
                    'last_checked_at' => $monitor->last_checked_at?->toIso8601String(),
                    'last_response_time_ms' => $monitor->last_response_time_ms,
                    'failure_reason' => $monitor->failure_reason,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentIncidents' => $recentIncidents,
            'devicesByStatus' => $devicesByStatus,
            'incidentsBySeverity' => $incidentsBySeverity,
            'phoneMonitors' => $phoneMonitors,
        ]);
    }
}

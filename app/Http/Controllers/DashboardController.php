<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Incident;
use App\Models\Organization;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_organizations' => Organization::count(),
            'total_devices' => Device::count(),
            'online_devices' => Device::where('status', 'online')->count(),
            'offline_devices' => Device::where('status', 'offline')->count(),
            'warning_devices' => Device::where('status', 'warning')->count(),
            'critical_devices' => Device::where('status', 'critical')->count(),
            'open_incidents' => Incident::open()->count(),
            'critical_incidents' => Incident::open()->where('severity', 'critical')->count(),
        ];

        $recentIncidents = Incident::with(['device.organization'])
            ->orderBy('occurred_at', 'desc')
            ->limit(10)
            ->get();

        $devicesByStatus = Device::selectRaw('status, COUNT(*) as count')
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

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentIncidents' => $recentIncidents,
            'devicesByStatus' => $devicesByStatus,
            'incidentsBySeverity' => $incidentsBySeverity,
        ]);
    }
}

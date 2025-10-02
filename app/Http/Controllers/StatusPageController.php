<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatusPageController extends Controller
{
    public function index(): Response
    {
        $since = Carbon::now()->subDay();
        $monitors = Monitor::query()
            ->select(['id', 'name', 'url', 'status', 'last_response_time_ms', 'last_checked_at'])
            ->orderBy('name')
            ->get();

        $uptime = MonitorCheck::query()
            ->select('monitor_id',
                DB::raw("SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up_count"),
                DB::raw('COUNT(*) as total_count'))
            ->where('checked_at', '>=', $since)
            ->groupBy('monitor_id')
            ->pluck('up_count', 'monitor_id');

        $totals = MonitorCheck::query()
            ->select('monitor_id', DB::raw('COUNT(*) as total_count'))
            ->where('checked_at', '>=', $since)
            ->groupBy('monitor_id')
            ->pluck('total_count', 'monitor_id');

        $items = $monitors->map(function ($m) use ($uptime, $totals) {
            $up = (int) ($uptime[$m->id] ?? 0);
            $total = (int) ($totals[$m->id] ?? 0);
            $pct = $total > 0 ? round(($up / max($total, 1)) * 100, 2) : null;
            return [
                'id' => $m->id,
                'name' => $m->name,
                'url' => $m->url,
                'status' => $m->status,
                'last_response_time_ms' => $m->last_response_time_ms,
                'last_checked_at' => optional($m->last_checked_at)->toIso8601String(),
                'uptime_24h' => $pct,
            ];
        });

        return Inertia::render('Status/Index', [
            'monitors' => $items,
            'since' => $since->toIso8601String(),
        ]);
    }
}


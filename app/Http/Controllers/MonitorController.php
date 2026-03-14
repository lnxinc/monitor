<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonitorRequest;
use App\Jobs\CheckMonitor;
use App\Models\Monitor;
use Inertia\Inertia;

class MonitorController extends Controller
{
    public function index()
    {
        $monitors = Monitor::orderBy('name')->get();

        return Inertia::render('Monitors/Index', [
            'monitors' => $monitors,
        ]);
    }

    public function create()
    {
        return Inertia::render('Monitors/Create');
    }

    public function store(MonitorRequest $request)
    {
        $attributes = $request->monitorAttributes();

        if (! isset($attributes['frequency_minutes']) && $attributes['type'] !== 'webhook') {
            $attributes['frequency_minutes'] = 5;
        }

        $monitor = Monitor::create($attributes);

        if ($monitor->type !== 'webhook') {
            CheckMonitor::dispatch($monitor);
        }

        $message = $monitor->type === 'webhook'
            ? 'Webhook monitor created. Token generated.'
            : 'Monitor created. First check queued.';

        return redirect()->route('monitors.index')->with('success', $message);
    }

    public function show(Monitor $monitor)
    {
        $monitor->load(['checks' => function ($q) {
            $q->latest('checked_at')->limit(50);
        }]);

        return Inertia::render('Monitors/Show', [
            'monitor' => $monitor,
        ]);
    }

    public function edit(Monitor $monitor)
    {
        return Inertia::render('Monitors/Edit', ['monitor' => $monitor]);
    }

    public function update(MonitorRequest $request, Monitor $monitor)
    {
        $attributes = $request->monitorAttributes();

        if (! isset($attributes['frequency_minutes']) && $attributes['type'] !== 'webhook') {
            $attributes['frequency_minutes'] = $monitor->frequency_minutes;
        }

        $monitor->update($attributes);

        return redirect()->route('monitors.index')->with('success', 'Monitor updated.');
    }

    public function destroy(Monitor $monitor)
    {
        $monitor->delete();

        return redirect()->route('monitors.index')->with('success', 'Monitor deleted.');
    }

    public function run(Monitor $monitor)
    {
        // Queue in background; requires a running queue worker
        if ($monitor->type === 'webhook') {
            return back()->with('info', 'Webhook monitors ingest via external calls.');
        }

        CheckMonitor::dispatch($monitor);

        return back()->with('success', 'Monitor check queued.');
    }
}

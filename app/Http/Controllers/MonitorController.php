<?php

namespace App\Http\Controllers;

use App\Jobs\CheckMonitor;
use App\Models\Monitor;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'frequency_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $monitor = Monitor::create($data);

        // Kick off first check
        CheckMonitor::dispatch($monitor);

        return redirect()->route('monitors.index')->with('success', 'Monitor created. First check queued.');
    }

    public function show(Monitor $monitor)
    {
        $monitor->load(['checks' => function ($q) { $q->latest('checked_at')->limit(50); }]);
        return Inertia::render('Monitors/Show', [
            'monitor' => $monitor,
        ]);
    }

    public function edit(Monitor $monitor)
    {
        return Inertia::render('Monitors/Edit', [ 'monitor' => $monitor ]);
    }

    public function update(Request $request, Monitor $monitor)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'frequency_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);
        $monitor->update($data);
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
        CheckMonitor::dispatch($monitor);
        return back()->with('success', 'Monitor check queued.');
    }
}

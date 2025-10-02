<?php

namespace App\Http\Controllers;

use App\Jobs\CheckMonitor;
use App\Models\Monitor;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    private const MONITOR_TYPES = ['http', 'ping', 'tcp', 'ssl', 'webhook'];

    public function store(Request $request)
    {
        $validated = $this->validateMonitor($request);

        [$target, $config] = $this->extractTargetAndConfig($validated);

        $monitor = Monitor::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'url' => $target,
            'config' => $config,
            'frequency_minutes' => $validated['frequency_minutes'] ?? 5,
        ]);

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
        $validated = $this->validateMonitor($request, $monitor);

        [$target, $config] = $this->extractTargetAndConfig($validated);

        $monitor->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'url' => $target,
            'config' => $config,
            'frequency_minutes' => $validated['frequency_minutes'] ?? $monitor->frequency_minutes,
        ]);

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

    private function validateMonitor(Request $request, ?Monitor $monitor = null): array
    {
        $type = $request->input('type', $monitor?->type ?? 'http');

        $portRules = ['nullable', 'integer', 'between:1,65535'];
        if (in_array($type, ['tcp', 'ssl'], true)) {
            $portRules[0] = 'required';
        }

        $frequencyRules = ['integer', 'min:1', 'max:1440'];
        if ($type === 'webhook') {
            array_unshift($frequencyRules, 'nullable');
        } else {
            array_unshift($frequencyRules, 'required');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(self::MONITOR_TYPES)],
            'url' => ['required', 'string', 'max:2048'],
            'frequency_minutes' => $frequencyRules,
            'config' => ['nullable', 'array'],
            'config.port' => $portRules,
            'config.timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
            'config.count' => ['nullable', 'integer', 'min:1', 'max:10'],
            'config.method' => ['nullable', Rule::in(['HEAD', 'GET'])],
            'config.expected_status' => ['nullable', 'integer', 'min:100', 'max:599'],
        ];

        if ($type === 'http') {
            $rules['url'][] = 'url';
        }

        return $request->validate($rules);
    }

    private function extractTargetAndConfig(array $data): array
    {
        $type = $data['type'];
        $target = trim($data['url']);
        $config = Arr::only($data['config'] ?? [], ['host', 'port', 'timeout', 'count', 'method', 'expected_status']);

        $config['target'] = $target;

        if (isset($config['port'])) {
            $config['port'] = (int) $config['port'];
        }
        if (isset($config['timeout'])) {
            $config['timeout'] = (int) $config['timeout'];
        }
        if (isset($config['count'])) {
            $config['count'] = (int) $config['count'];
        }
        if (isset($config['expected_status'])) {
            $config['expected_status'] = (int) $config['expected_status'];
        }

        if ($type === 'ping') {
            $config['host'] = $target;
        }

        if ($type === 'tcp') {
            $config['host'] = $config['host'] ?? $target;
            if (isset($config['host'], $config['port'])) {
                $config['target'] = sprintf('%s:%s', $config['host'], $config['port']);
            }
        }

        if ($type === 'ssl') {
            $config['host'] = $config['host'] ?? $target;
            $config['port'] = $config['port'] ?? 443;
            if (isset($config['host'], $config['port'])) {
                $config['target'] = sprintf('%s:%s', $config['host'], $config['port']);
            }
        }

        $config = array_filter($config, fn ($value) => ! is_null($value));

        return [$target, $config];
    }
}

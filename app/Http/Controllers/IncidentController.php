<?php

namespace App\Http\Controllers;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Incident::with(['device.organization'])
            ->orderBy('occurred_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Preserve query string so pagination keeps current filters applied
        $incidents = $query->paginate(20)->withQueryString();

        $devices = Device::query()
            ->with(['organization:id,name'])
            ->select(['id', 'name', 'organization_id'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Incidents/Index', [
            'incidents' => $incidents,
            'filters' => $request->only(['status', 'severity']),
            'devices' => $devices,
        ]);
    }

    public function show(Incident $incident)
    {
        $incident->load(['device.organization']);

        return Inertia::render('Incidents/Show', [
            'incident' => $incident,
        ]);
    }

    public function acknowledge(Incident $incident)
    {
        $incident->acknowledge();

        return back()->with('success', 'Incident acknowledged successfully.');
    }

    public function resolve(Request $request, Incident $incident)
    {
        $data = $request->validate([
            'resolution_comment' => 'nullable|string|max:2000',
        ]);

        $incident->resolve($data['resolution_comment'] ?? null);

        // If the device has no other open incidents, mark it back online
        $incident->load('device');
        if ($incident->device) {
            $hasOpenIncidents = $incident->device
                ->incidents()
                ->where('status', 'open')
                ->exists();

            if (! $hasOpenIncidents) {
                $incident->device->update([
                    'status' => DeviceStatus::Online,
                    'last_seen_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Incident resolved successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'severity' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'occurred_at' => 'nullable|date',
            'device_status' => ['nullable', Rule::enum(DeviceStatus::class)],
        ]);

        $device = Device::findOrFail($validated['device_id']);

        $incident = Incident::create([
            'device_id' => $device->id,
            'severity' => $validated['severity'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'open',
            'occurred_at' => $validated['occurred_at'] ?? now(),
        ]);

        $device->update([
            'status' => $validated['device_status'] ?? DeviceStatus::Warning,
            'last_seen_at' => now(),
        ]);

        return redirect()->route('incidents.index')->with('success', 'Incident created successfully.');
    }
}

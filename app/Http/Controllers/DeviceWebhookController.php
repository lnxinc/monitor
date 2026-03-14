<?php

namespace App\Http\Controllers;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceWebhookController extends Controller
{
    public function reportIncident(Request $request, string $secret): JsonResponse
    {
        $device = Device::where('secret', $secret)->first();

        if (! $device) {
            return response()->json([
                'message' => 'Device not found.',
            ], 404);
        }

        $validated = $request->validate([
            'severity' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
            'device_status' => ['nullable', Rule::enum(DeviceStatus::class)],
        ]);

        $incident = Incident::create([
            'device_id' => $device->id,
            'severity' => $validated['severity'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'open',
            'occurred_at' => now(),
            'metadata' => $validated['metadata'] ?? null,
        ]);

        $device->update([
            'status' => $validated['device_status'] ?? DeviceStatus::Warning,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'message' => 'Incident created and device status updated.',
            'device' => [
                'id' => $device->id,
                'status' => $device->status,
                'last_seen_at' => optional($device->last_seen_at)->toIso8601String(),
            ],
            'incident' => [
                'id' => $incident->id,
                'status' => $incident->status,
                'severity' => $incident->severity,
                'occurred_at' => optional($incident->occurred_at)->toIso8601String(),
            ],
        ], 201);
    }
}

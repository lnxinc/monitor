<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Services\Monitoring\MonitorResult;
use App\Services\Monitoring\MonitorStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonitorWebhookController extends Controller
{
    public function ingest(Request $request, string $token, MonitorStateService $stateService): JsonResponse
    {
        $monitor = Monitor::where('webhook_token', $token)->first();

        if (! $monitor || $monitor->type !== 'webhook') {
            return response()->json(['message' => 'Monitor not found.'], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:up,down'],
            'status_code' => ['nullable', 'integer', 'min:100', 'max:599'],
            'response_time_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
            'error' => ['nullable', 'string', 'max:2048'],
            'meta' => ['nullable', 'array'],
            'payload' => ['nullable', 'array'],
            'checked_at' => ['nullable', 'date'],
        ]);

        $result = new MonitorResult(
            status: $validated['status'],
            statusCode: $validated['status_code'] ?? null,
            responseTimeMs: $validated['response_time_ms'] ?? null,
            error: $validated['error'] ?? null,
            meta: $validated['meta'] ?? [],
        );

        if (isset($validated['payload'])) {
            $result = $result->withPayload($validated['payload']);
        }

        if (! empty($validated['checked_at'])) {
            $result = $result->withCheckedAt(Carbon::parse($validated['checked_at']));
        }

        $stateService->apply($monitor, $result);

        return response()->json([
            'message' => 'Monitor state updated.',
            'monitor_id' => $monitor->id,
        ], 202);
    }
}


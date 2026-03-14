<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonitorRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MonitorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $monitors = Monitor::query()->orderBy('name')->paginate(25);

        return MonitorResource::collection($monitors);
    }

    public function store(MonitorRequest $request): JsonResponse
    {
        $attributes = $request->monitorAttributes();

        if (! isset($attributes['frequency_minutes']) && $attributes['type'] !== 'webhook') {
            $attributes['frequency_minutes'] = 5;
        }

        $monitor = Monitor::create($attributes);

        return (new MonitorResource($monitor->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Monitor $monitor): MonitorResource
    {
        $monitor->load(['policies.channels']);

        return new MonitorResource($monitor);
    }

    public function update(MonitorRequest $request, Monitor $monitor): MonitorResource
    {
        $attributes = $request->monitorAttributes();

        if (! isset($attributes['frequency_minutes']) && $attributes['type'] !== 'webhook') {
            $attributes['frequency_minutes'] = $monitor->frequency_minutes;
        }

        $monitor->update($attributes);

        return new MonitorResource($monitor->fresh());
    }

    public function destroy(Monitor $monitor): Response
    {
        $monitor->delete();

        return response()->noContent();
    }
}

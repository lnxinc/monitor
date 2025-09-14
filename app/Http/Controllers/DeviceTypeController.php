<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceType\StoreDeviceTypeRequest;
use App\Http\Requests\DeviceType\UpdateDeviceTypeRequest;
use App\Models\DeviceType;
use Inertia\Inertia;

class DeviceTypeController extends Controller
{
    public function index()
    {
        $types = DeviceType::withCount('devices')->orderBy('name')->get();

        return Inertia::render('DeviceTypes/Index', [
            'types' => $types,
        ]);
    }

    public function create()
    {
        return Inertia::render('DeviceTypes/Create');
    }

    public function store(StoreDeviceTypeRequest $request)
    {
        DeviceType::create($request->validated());
        return redirect()->route('device-types.index')->with('success', 'Device type created successfully.');
    }

    public function edit(DeviceType $deviceType)
    {
        return Inertia::render('DeviceTypes/Edit', [
            'type' => $deviceType,
        ]);
    }

    public function update(UpdateDeviceTypeRequest $request, DeviceType $deviceType)
    {
        $deviceType->update($request->validated());
        return redirect()->route('device-types.index')->with('success', 'Device type updated successfully.');
    }

    public function destroy(DeviceType $deviceType)
    {
        $deviceType->delete();
        return redirect()->route('device-types.index')->with('success', 'Device type deleted successfully.');
    }
}


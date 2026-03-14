<?php

namespace App\Http\Controllers;

use App\Http\Requests\Device\StoreDeviceRequest;
use App\Http\Requests\Device\UpdateDeviceRequest;
use App\Models\Device;
use App\Models\Organization;
use Inertia\Inertia;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::with(['organization','deviceType'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Devices/Index', [
            'devices' => $devices,
        ]);
    }

    public function create()
    {
        $organizations = Organization::orderBy('name')->get();
        $deviceTypes = \App\Models\DeviceType::orderBy('name')->get();

        return Inertia::render('Devices/Create', [
            'organizations' => $organizations,
            'deviceTypes' => $deviceTypes,
        ]);
    }

    public function store(StoreDeviceRequest $request)
    {
        Device::create($request->validated());

        return redirect()->route('devices.index')
            ->with('success', 'Device created successfully.');
    }

    public function show(Device $device)
    {
        $device->load([
            'organization',
            'deviceType',
            'incidents' => function ($query) {
                $query->orderBy('occurred_at', 'desc')->limit(10);
            }
        ]);

        return Inertia::render('Devices/Show', [
            'device' => $device,
        ]);
    }

    public function edit(Device $device)
    {
        $organizations = Organization::orderBy('name')->get();
        $deviceTypes = \App\Models\DeviceType::orderBy('name')->get();
        $device->load('organization');

        return Inertia::render('Devices/Edit', [
            'device' => $device,
            'organizations' => $organizations,
            'deviceTypes' => $deviceTypes,
        ]);
    }

    public function update(UpdateDeviceRequest $request, Device $device)
    {
        $device->update($request->validated());

        return redirect()->route('devices.index')
            ->with('success', 'Device updated successfully.');
    }

    public function destroy(Device $device)
    {
        $device->delete();

        return redirect()->route('devices.index')
            ->with('success', 'Device deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceWindowController extends Controller
{
    public function index()
    {
        $windows = MaintenanceWindow::orderByDesc('start_at')->get();
        return Inertia::render('Maintenance/Index', [ 'windows' => $windows ]);
    }

    public function create()
    {
        return Inertia::render('Maintenance/Create', [
            'organizations' => Organization::orderBy('name')->get(['id','name']),
            'devices' => Device::orderBy('name')->get(['id','name']),
            'monitors' => Monitor::orderBy('name')->get(['id','name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'scope' => ['required','in:global,organization,device,monitor'],
            'ref_id' => ['nullable','integer'],
            'start_at' => ['required','date'],
            'end_at' => ['required','date','after:start_at'],
            'reason' => ['nullable','string','max:255'],
        ]);
        MaintenanceWindow::create($data);
        return redirect()->route('maintenance.index')->with('success','Maintenance window created.');
    }

    public function edit(MaintenanceWindow $maintenance)
    {
        return Inertia::render('Maintenance/Edit', [
            'maintenance' => $maintenance,
            'organizations' => Organization::orderBy('name')->get(['id','name']),
            'devices' => Device::orderBy('name')->get(['id','name']),
            'monitors' => Monitor::orderBy('name')->get(['id','name']),
        ]);
    }

    public function update(Request $request, MaintenanceWindow $maintenance)
    {
        $data = $request->validate([
            'scope' => ['required','in:global,organization,device,monitor'],
            'ref_id' => ['nullable','integer'],
            'start_at' => ['required','date'],
            'end_at' => ['required','date','after:start_at'],
            'reason' => ['nullable','string','max:255'],
        ]);
        $maintenance->update($data);
        return redirect()->route('maintenance.index')->with('success','Maintenance window updated.');
    }

    public function destroy(MaintenanceWindow $maintenance)
    {
        $maintenance->delete();
        return redirect()->route('maintenance.index')->with('success','Maintenance window deleted.');
    }
}


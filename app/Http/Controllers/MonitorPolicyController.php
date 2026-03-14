<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorPolicy;
use App\Models\NotificationChannel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonitorPolicyController extends Controller
{
    public function index()
    {
        $policies = MonitorPolicy::with(['monitor:id,name', 'channels:id,name,type'])->get();
        return Inertia::render('Monitors/Policies/Index', [
            'policies' => $policies,
        ]);
    }

    public function create()
    {
        return Inertia::render('Monitors/Policies/Create', [
            'monitors' => Monitor::orderBy('name')->get(['id','name']),
            'channels' => NotificationChannel::where('is_active', true)->orderBy('name')->get(['id','name','type']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'monitor_id' => ['required','exists:monitors,id'],
            'down_threshold' => ['required','integer','min:1','max:10'],
            'notify_after_seconds' => ['nullable','integer','min:0','max:3600'],
            'repeat_interval_minutes' => ['nullable','integer','min:1','max:1440'],
            'notify_on_recovery' => ['sometimes','boolean'],
            'enabled' => ['sometimes','boolean'],
            'channel_ids' => ['array'],
            'channel_ids.*' => ['exists:notification_channels,id'],
        ]);
        $policy = MonitorPolicy::create([
            'monitor_id' => $data['monitor_id'],
            'down_threshold' => $data['down_threshold'],
            'notify_after_seconds' => $data['notify_after_seconds'] ?? 0,
            'repeat_interval_minutes' => $data['repeat_interval_minutes'] ?? null,
            'notify_on_recovery' => (bool)($data['notify_on_recovery'] ?? true),
            'enabled' => (bool)($data['enabled'] ?? true),
        ]);
        $policy->channels()->sync($data['channel_ids'] ?? []);
        return redirect()->route('policies.index')->with('success','Policy created.');
    }

    public function edit(MonitorPolicy $policy)
    {
        return Inertia::render('Monitors/Policies/Edit', [
            'policy' => $policy->load('channels:id'),
            'monitors' => Monitor::orderBy('name')->get(['id','name']),
            'channels' => NotificationChannel::where('is_active', true)->orderBy('name')->get(['id','name','type']),
        ]);
    }

    public function update(Request $request, MonitorPolicy $policy)
    {
        $data = $request->validate([
            'monitor_id' => ['required','exists:monitors,id'],
            'down_threshold' => ['required','integer','min:1','max:10'],
            'notify_after_seconds' => ['nullable','integer','min:0','max:3600'],
            'repeat_interval_minutes' => ['nullable','integer','min:1','max:1440'],
            'notify_on_recovery' => ['sometimes','boolean'],
            'enabled' => ['sometimes','boolean'],
            'channel_ids' => ['array'],
            'channel_ids.*' => ['exists:notification_channels,id'],
        ]);
        $policy->update([
            'monitor_id' => $data['monitor_id'],
            'down_threshold' => $data['down_threshold'],
            'notify_after_seconds' => $data['notify_after_seconds'] ?? 0,
            'repeat_interval_minutes' => $data['repeat_interval_minutes'] ?? null,
            'notify_on_recovery' => (bool)($data['notify_on_recovery'] ?? true),
            'enabled' => (bool)($data['enabled'] ?? true),
        ]);
        $policy->channels()->sync($data['channel_ids'] ?? []);
        return redirect()->route('policies.index')->with('success','Policy updated.');
    }

    public function destroy(MonitorPolicy $policy)
    {
        $policy->delete();
        return redirect()->route('policies.index')->with('success','Policy deleted.');
    }
}


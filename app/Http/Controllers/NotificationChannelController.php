<?php

namespace App\Http\Controllers;

use App\Models\NotificationChannel;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationChannelController extends Controller
{
    public function index()
    {
        $channels = NotificationChannel::with('organization')->orderBy('name')->get();
        return Inertia::render('Alerts/Channels/Index', [
            'channels' => $channels,
        ]);
    }

    public function create()
    {
        return Inertia::render('Alerts/Channels/Create', [
            'organizations' => Organization::orderBy('name')->get(['id','name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_id' => ['nullable','exists:organizations,id'],
            'name' => ['required','string','max:255'],
            'type' => ['required','in:email,slack_webhook'],
            'config' => ['nullable','array'],
            'is_active' => ['sometimes','boolean'],
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? true);
        NotificationChannel::create($data);
        return redirect()->route('channels.index')->with('success','Channel created.');
    }

    public function edit(NotificationChannel $channel)
    {
        return Inertia::render('Alerts/Channels/Edit', [
            'channel' => $channel,
            'organizations' => Organization::orderBy('name')->get(['id','name']),
        ]);
    }

    public function update(Request $request, NotificationChannel $channel)
    {
        $data = $request->validate([
            'organization_id' => ['nullable','exists:organizations,id'],
            'name' => ['required','string','max:255'],
            'type' => ['required','in:email,slack_webhook'],
            'config' => ['nullable','array'],
            'is_active' => ['sometimes','boolean'],
        ]);
        $channel->update($data);
        return redirect()->route('channels.index')->with('success','Channel updated.');
    }

    public function destroy(NotificationChannel $channel)
    {
        $channel->delete();
        return redirect()->route('channels.index')->with('success','Channel deleted.');
    }
}


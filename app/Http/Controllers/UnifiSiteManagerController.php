<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Incident;
use App\Models\Organization;
use App\Services\UnifiSiteManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnifiSiteManagerController extends Controller
{
    public function __construct(private UnifiSiteManager $svc) {}

    public function index(): Response
    {
        return Inertia::render('Unifi/Index', [
            'hasApiKey' => $this->svc->hasApiKey(),
            'baseUrl' => rtrim((string) config('unifi.base_url'), '/'),
        ]);
    }

    public function sync(): RedirectResponse
    {
        $summary = $this->svc->syncIntoDatabase();
        return back()->with('success', sprintf(
            'UniFi sync complete. Sites: %d, created: %d, updated: %d, incidents raised: %d.',
            $summary['total'], $summary['created'], $summary['updated'], $summary['incidents']
        ));
    }
}

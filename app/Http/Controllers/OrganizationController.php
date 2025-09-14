<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::withCount('devices')->get();

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
        ]);
    }

    public function create()
    {
        return Inertia::render('Organizations/Create');
    }

    public function store(StoreOrganizationRequest $request)
    {
        Organization::create($request->validated());

        return redirect()->route('organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization)
    {
        $organization->load(['devices' => function ($query) {
            $query->with('incidents')->orderBy('name');
        }]);

        return Inertia::render('Organizations/Show', [
            'organization' => $organization,
        ]);
    }

    public function edit(Organization $organization)
    {
        return Inertia::render('Organizations/Edit', [
            'organization' => $organization,
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $organization->update($request->validated());

        return redirect()->route('organizations.index')
            ->with('success', 'Organization updated successfully.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();

        return redirect()->route('organizations.index')
            ->with('success', 'Organization deleted successfully.');
    }
}

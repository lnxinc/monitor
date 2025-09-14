<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Incident;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class MonitoringSeeder extends Seeder
{
    public function run(): void
    {
        // Create organizations
        $organizations = Organization::factory(5)->create();

        // Create devices for each organization
        $organizations->each(function (Organization $organization) {
            // Create devices with mixed statuses
            Device::factory(3)->online()->create(['organization_id' => $organization->id]);
            Device::factory(2)->offline()->create(['organization_id' => $organization->id]);
            Device::factory(1)->critical()->create(['organization_id' => $organization->id]);
            Device::factory(1)->create([
                'organization_id' => $organization->id,
                'status' => 'warning',
            ]);
        });

        // Create incidents for devices
        Device::all()->each(function (Device $device) {
            // Create a mix of incidents
            Incident::factory(2)->open()->create(['device_id' => $device->id]);
            Incident::factory(1)->create([
                'device_id' => $device->id,
                'status' => 'acknowledged',
            ]);
            Incident::factory(3)->create([
                'device_id' => $device->id,
                'status' => 'resolved',
            ]);

            // Create some critical incidents for critical devices
            if ($device->status === 'critical') {
                Incident::factory(2)->critical()->open()->create(['device_id' => $device->id]);
            }
        });
    }
}

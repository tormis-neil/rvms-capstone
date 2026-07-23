<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Sample repair logs so the page demonstrates itself (plan R4 Day 8):
 * per agency, one internal repair and one external-shop repair.
 */
class RepairLogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Agency::all() as $agency) {
            $vehicle = Vehicle::query()
                ->where('agency_id', $agency->id)
                ->orderBy('id')
                ->first();

            if (! $vehicle) {
                continue;
            }

            $driverId = $vehicle->assigned_driver_id
                ?? User::query()->where('agency_id', $agency->id)->where('role', User::ROLE_DRIVER)->value('id');

            RepairLog::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driverId,
                'repair_date' => now()->subDays(9)->toDateString(),
                'scope_of_work' => 'Front brake pad replacement',
                'parts_replaced' => 'Brake pads (front set)',
                'cost' => 4800.00,
                'repair_source' => RepairLog::SOURCE_INTERNAL,
                'remarks' => 'From damage report; resolved.',
            ]);

            RepairLog::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driverId,
                'repair_date' => now()->subDays(2)->toDateString(),
                'scope_of_work' => 'Transmission assessment and teardown',
                'parts_replaced' => null,
                'cost' => null,
                'repair_source' => RepairLog::SOURCE_EXTERNAL,
                'external_shop_name' => 'Calbayog Auto Works',
                'remarks' => 'Overhaul required; coordinate follow-up.',
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Sample dispatches so the page demonstrates itself (plan R6): per agency, one
 * active mission (vehicle currently Dispatched) and one completed record.
 */
class DispatchSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Agency::all() as $agency) {
            $driver = User::query()
                ->where('agency_id', $agency->id)
                ->where('role', User::ROLE_DRIVER)
                ->orderBy('id')
                ->first();

            // Use two distinct vehicles when available so the active one can stay
            // Dispatched without clashing with other seeded statuses.
            $vehicles = Vehicle::query()
                ->where('agency_id', $agency->id)
                ->orderBy('id')
                ->get();

            if ($driver === null || $vehicles->isEmpty()) {
                continue;
            }

            $activeVehicle = $vehicles->first();
            $completedVehicle = $vehicles->count() > 1 ? $vehicles[1] : $vehicles->first();

            // Active mission — vehicle currently deployed.
            Dispatch::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $activeVehicle->id,
                'driver_id' => $driver->id,
                'mission_type' => 'Patrol',
                'location' => 'Brgy. Obrero, Calbayog',
                'time_out' => now()->subHours(2),
                'odometer_out' => $activeVehicle->current_mileage,
            ]);
            $activeVehicle->update(['status' => Vehicle::STATUS_DISPATCHED]);

            // Completed record.
            Dispatch::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $completedVehicle->id,
                'driver_id' => $driver->id,
                'mission_type' => 'Administrative Travel',
                'location' => 'Brgy. Aguit-itan, Calbayog',
                'time_out' => now()->subDays(3)->setTime(14, 10),
                'time_in' => now()->subDays(3)->setTime(17, 35),
                'return_status' => Vehicle::STATUS_OPERATIONAL,
                'remarks' => 'Returned without incident.',
            ]);
        }
    }
}

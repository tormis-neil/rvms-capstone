<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Sample damage reports so the page demonstrates itself (plan R4 Day 7):
 * per agency, today's report still Pending and an earlier one already Reviewed.
 */
class DamageReportSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Agency::all() as $agency) {
            $driver = User::query()
                ->where('agency_id', $agency->id)
                ->where('role', User::ROLE_DRIVER)
                ->orderBy('id')
                ->first();

            $vehicle = Vehicle::query()
                ->where('agency_id', $agency->id)
                ->orderBy('id')
                ->first();

            $admin = User::query()
                ->where('agency_id', $agency->id)
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('id')
                ->first();

            if (! $driver || ! $vehicle) {
                continue;
            }

            // Today — Pending.
            DamageReport::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'nature_of_damage' => 'Cracked side mirror (driver side)',
                'suspected_parts' => 'Side mirror assembly',
                'date_reported' => now()->toDateString(),
                'status' => DamageReport::STATUS_PENDING,
                'created_at' => now()->setTime(8, 10),
                'updated_at' => now()->setTime(8, 10),
            ]);

            // Earlier — already Reviewed.
            DamageReport::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'nature_of_damage' => 'Brake pad wear — unusual noise during braking',
                'suspected_parts' => 'Front brake pads',
                'date_reported' => now()->subDays(11)->toDateString(),
                'status' => DamageReport::STATUS_REVIEWED,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now()->subDays(11),
                'created_at' => now()->subDays(11)->setTime(9, 20),
                'updated_at' => now()->subDays(11)->setTime(9, 20),
            ]);
        }
    }
}

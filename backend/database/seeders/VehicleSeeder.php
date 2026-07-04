<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Sample vehicles per agency (FR-05) so the dashboard pages have
     * data out of the box. The first vehicle of each agency is assigned
     * to that agency's first seeded driver.
     */
    public function run(): void
    {
        $fleet = [
            'BFP' => [
                ['type' => 'Fire Truck', 'plate_number' => 'SFK-301', 'make' => 'Isuzu', 'model' => 'FTR 850'],
                ['type' => 'Fire Truck', 'plate_number' => 'SFK-302', 'make' => 'Morita', 'model' => 'FFAB-30'],
            ],
            'PNP' => [
                ['type' => 'Patrol Car', 'plate_number' => 'PNP-410', 'make' => 'Toyota', 'model' => 'Hilux'],
                ['type' => 'Patrol Car', 'plate_number' => 'PNP-411', 'make' => 'Mitsubishi', 'model' => 'Montero Sport'],
            ],
            'CDRRMO' => [
                ['type' => 'Rescue Truck', 'plate_number' => 'CDR-520', 'make' => 'Isuzu', 'model' => 'NPS 75'],
                ['type' => 'Rescue Van', 'plate_number' => 'CDR-521', 'make' => 'Toyota', 'model' => 'Hiace'],
            ],
            'CHO' => [
                ['type' => 'Ambulance', 'plate_number' => 'CHO-630', 'make' => 'Toyota', 'model' => 'Hiace'],
                ['type' => 'Ambulance', 'plate_number' => 'CHO-631', 'make' => 'Nissan', 'model' => 'Urvan'],
            ],
        ];

        foreach (Agency::all() as $agency) {
            $firstDriver = User::drivers()
                ->where('agency_id', $agency->id)
                ->orderBy('id')
                ->first();

            foreach ($fleet[$agency->code] ?? [] as $index => $vehicle) {
                Vehicle::withoutGlobalScopes()->updateOrCreate(
                    ['agency_id' => $agency->id, 'plate_number' => $vehicle['plate_number']],
                    $vehicle + [
                        'agency_id' => $agency->id,
                        'assigned_driver_id' => $index === 0 ? $firstDriver?->id : null,
                        'engine_number' => 'ENG-'.$agency->code.'-'.($index + 1),
                        'chassis_number' => 'CHS-'.$agency->code.'-'.($index + 1),
                        'current_mileage' => 12000 + ($index * 8500),
                        'status' => Vehicle::STATUS_OPERATIONAL,
                    ],
                );
            }
        }
    }
}

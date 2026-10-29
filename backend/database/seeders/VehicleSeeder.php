<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Two sample vehicles per agency (plan R2.4); the first is assigned to the
 * agency's first driver. Data mirrors the prototype's demo fleet. No vehicle
 * is seeded as "Dispatched" — that status only comes from an open dispatch
 * record (FR-15/FR-18), and the Dispatch module arrives in R6.
 */
class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $fleet = [
            'BFP' => [
                ['plate_number' => 'ABC-1234', 'type' => 'Fire Truck', 'make' => 'Isuzu', 'model' => 'FTR 850', 'engine_number' => '4HK1-TC-587234', 'chassis_number' => 'JALC4W14697100345', 'current_mileage' => 45230, 'status' => Vehicle::STATUS_OPERATIONAL, 'remarks' => 'Baseline intake inspection completed; pump, hydraulics, and drivetrain in good working order.'],
                ['plate_number' => 'EFG-4532', 'type' => 'Water Tanker', 'make' => 'Isuzu', 'model' => 'FVR', 'engine_number' => '6HK1-XS-778812', 'chassis_number' => 'JALFVR34LC7000891', 'current_mileage' => 81650, 'status' => Vehicle::STATUS_UNDER_PM, 'remarks' => 'Scheduled preventive maintenance in progress for engine oil and filter replacement.'],
            ],
            'PNP' => [
                ['plate_number' => 'PNP-1021', 'type' => 'Patrol Car', 'make' => 'Toyota', 'model' => 'Vios', 'engine_number' => '2NR-FE-118723', 'chassis_number' => 'MR053KKB401029384', 'current_mileage' => 72300, 'status' => Vehicle::STATUS_OPERATIONAL, 'remarks' => 'Baseline intake check passed; patrol unit cleared for station deployment.'],
                ['plate_number' => 'PNP-2234', 'type' => 'Patrol Motorcycle', 'make' => 'Yamaha', 'model' => 'Sniper 155', 'engine_number' => 'G3K4E-771204', 'chassis_number' => 'MH3SG6920LK033410', 'current_mileage' => 33780, 'status' => Vehicle::STATUS_UNDER_PM, 'remarks' => 'Undergoing routine chain, sprocket, and brake pad servicing.'],
            ],
            'CDRRMO' => [
                ['plate_number' => 'CDR-3301', 'type' => 'Rescue Truck', 'make' => 'Isuzu', 'model' => 'ELF', 'engine_number' => '4HF1-RT-330218', 'chassis_number' => 'JAANKR55LH7700213', 'current_mileage' => 47800, 'status' => Vehicle::STATUS_OPERATIONAL, 'remarks' => 'Baseline intake assessment complete; rescue equipment mountings and engine verified operational.'],
                ['plate_number' => 'CDR-3370', 'type' => 'Service Truck', 'make' => 'Hino', 'model' => '300', 'engine_number' => 'N04C-ST-883011', 'chassis_number' => 'JHHADM2H50K112940', 'current_mileage' => 91200, 'status' => Vehicle::STATUS_NOT_OPERATIONAL, 'remarks' => 'Grounded pending suspension leaf spring and brake line replacement at GSO Motorpool.'],
            ],
            'CHO' => [
                ['plate_number' => 'CHO-4401', 'type' => 'Ambulance', 'make' => 'Toyota', 'model' => 'Hiace', 'engine_number' => '1KD-AM-441120', 'chassis_number' => 'JTFSK22P9L0119033', 'current_mileage' => 58400, 'status' => Vehicle::STATUS_OPERATIONAL, 'remarks' => 'Baseline intake inspection passed; oxygen mounts, siren, and engine cleared for medical transport.'],
                ['plate_number' => 'CHO-4456', 'type' => 'Service Vehicle', 'make' => 'Mitsubishi', 'model' => 'L300', 'engine_number' => '4D56-SV-996475', 'chassis_number' => 'MMBJNKA70LD041208', 'current_mileage' => 94300, 'status' => Vehicle::STATUS_NOT_OPERATIONAL, 'remarks' => 'Grounded due to radiator overheating and worn alternator belt.'],
            ],
        ];

        foreach (Agency::all() as $agency) {
            $firstDriver = User::query()
                ->where('agency_id', $agency->id)
                ->where('role', User::ROLE_DRIVER)
                ->orderBy('id')
                ->first();

            foreach ($fleet[$agency->code] ?? [] as $index => $vehicle) {
                Vehicle::updateOrCreate(
                    ['agency_id' => $agency->id, 'plate_number' => $vehicle['plate_number']],
                    $vehicle + [
                        'agency_id' => $agency->id,
                        'assigned_driver_id' => $index === 0 ? $firstDriver?->id : null,
                    ],
                );
            }
        }
    }
}

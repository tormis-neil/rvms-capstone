<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed one agency administrator and sample drivers per agency.
     * Admin accounts are provisioned only (no admin self-registration).
     */
    public function run(): void
    {
        $drivers = [
            'BFP' => [
                ['name' => 'Ramon Villanueva', 'email' => 'ramon.villanueva@rvms.local'],
                ['name' => 'Carlos Dizon', 'email' => 'carlos.dizon@rvms.local'],
            ],
            'PNP' => [
                ['name' => 'Elena Marasigan', 'email' => 'elena.marasigan@rvms.local'],
                ['name' => 'Joseph Cabrera', 'email' => 'joseph.cabrera@rvms.local'],
            ],
            'CDRRMO' => [
                ['name' => 'Marvin Salazar', 'email' => 'marvin.salazar@rvms.local'],
                ['name' => 'Rico Bautista', 'email' => 'rico.bautista@rvms.local'],
            ],
            'CHO' => [
                ['name' => 'Andrea Fuentes', 'email' => 'andrea.fuentes@rvms.local'],
                ['name' => 'Dennis Ocampo', 'email' => 'dennis.ocampo@rvms.local'],
            ],
        ];

        foreach (Agency::all() as $agency) {
            User::updateOrCreate(
                ['email' => strtolower($agency->code).'.admin@rvms.local'],
                [
                    'agency_id' => $agency->id,
                    'role' => User::ROLE_ADMIN,
                    'name' => $agency->code.' Administrator',
                    'password' => 'password',
                    'status' => User::STATUS_ACTIVE,
                ],
            );

            // An agency may have more than one administrator account (per the
            // interviews: e.g., logistics and operations officers). Seed a
            // second BFP admin as the demonstrable sample.
            if ($agency->code === 'BFP') {
                User::updateOrCreate(
                    ['email' => 'bfp.admin2@rvms.local'],
                    [
                        'agency_id' => $agency->id,
                        'role' => User::ROLE_ADMIN,
                        'name' => 'BFP Deputy Administrator',
                        'password' => 'password',
                        'status' => User::STATUS_ACTIVE,
                    ],
                );
            }

            foreach ($drivers[$agency->code] ?? [] as $index => $driver) {
                User::updateOrCreate(
                    ['email' => $driver['email']],
                    [
                        'agency_id' => $agency->id,
                        'role' => User::ROLE_DRIVER,
                        'name' => $driver['name'],
                        'password' => 'password',
                        'status' => User::STATUS_ACTIVE,
                        'license_number' => sprintf('D%02d-24-%06d', $agency->id, ($agency->id * 100) + $index + 1),
                        'license_expiry_date' => now()->addMonths(6 + ($index * 6))->toDateString(),
                    ],
                );
            }
        }
    }
}

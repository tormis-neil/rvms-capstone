<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed authentic historical UAT records for CDRRMO Unassigned Reserve Vehicles (SHA-2006 and AND-9023).
 *
 * Rules:
 * - Direct DB::table inserts to strictly preserve past created_at timestamps.
 * - Dispatches: Sept 16 to Oct 08, 2026 (weekdays only, 9am - 5pm).
 * - Language: Simple English + authentic Calbayognon Waray (strictly using "san" and "sa", replacing all "han" and "ha").
 * - Odometer progressions lead exactly to the vehicles' current odometers (60,475 km and 64,950 km).
 * - Preserves all users, drivers, and other vehicle records in CDRRMO.
 */
class SeedCdrrmoUnassignedCommand extends Command
{
    protected $signature = 'rvms:seed-cdrrmo-unassigned';

    protected $description = 'Seed authentic historical UAT records for CDRRMO Unassigned Reserve Vehicles (SHA-2006 & AND-9023)';

    public function handle(): int
    {
        $agency = Agency::where('code', 'CDRRMO')->first();
        if (! $agency) {
            $this->error('CDRRMO agency record not found!');
            return self::FAILURE;
        }

        $admin = User::where('email', 'cdrrmo.admin@email.com')->first()
            ?? User::where('agency_id', $agency->id)->where('role', User::ROLE_ADMIN)->first();

        if (! $admin) {
            $this->error('CDRRMO admin user not found!');
            return self::FAILURE;
        }

        $v2006 = Vehicle::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'SHA-2006')
                  ->orWhere('plate_number', 'SHA 2006');
            })->first();

        if (! $v2006) {
            $this->error('Vehicle SHA-2006 not found in CDRRMO fleet.');
            return self::FAILURE;
        }

        $v9023 = Vehicle::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'AND-9023')
                  ->orWhere('plate_number', 'AND 9023');
            })->first();

        if (! $v9023) {
            $this->error('Vehicle AND-9023 not found in CDRRMO fleet.');
            return self::FAILURE;
        }

        $calbiga = User::where('email', 'calbiga@email.com')->first();
        $delaPena = User::where('email', 'jonathan@email.com')->first();
        $mabini = User::where('email', 'mabini@email.com')->first();

        if (! $calbiga || ! $delaPena || ! $mabini) {
            $this->error('One or more CDRRMO drivers (calbiga@email.com, jonathan@email.com, mabini@email.com) missing.');
            return self::FAILURE;
        }

        $vehicleIds = [$v2006->id, $v9023->id];

        $this->info('Cleaning up existing logs for SHA-2006 and AND-9023 to ensure idempotency...');

        // Clear previous repair logs for both vehicles
        DB::table('repair_logs')
            ->where('agency_id', $agency->id)
            ->whereIn('vehicle_id', $vehicleIds)
            ->delete();

        // Clear previous PM schedules for both vehicles
        DB::table('pm_schedules')
            ->where('agency_id', $agency->id)
            ->whereIn('vehicle_id', $vehicleIds)
            ->delete();

        // Clear previous dispatches for both vehicles
        DB::table('dispatches')
            ->where('agency_id', $agency->id)
            ->whereIn('vehicle_id', $vehicleIds)
            ->delete();

        // Clear previous notifications for these two vehicles
        DB::table('notifications')
            ->where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('message', 'like', '%SHA-2006%')
                  ->orWhere('message', 'like', '%SHA 2006%')
                  ->orWhere('message', 'like', '%AND-9023%')
                  ->orWhere('message', 'like', '%AND 9023%')
                  ->orWhere('data', 'like', '%SHA-2006%')
                  ->orWhere('data', 'like', '%SHA 2006%')
                  ->orWhere('data', 'like', '%AND-9023%')
                  ->orWhere('data', 'like', '%AND 9023%');
            })
            ->delete();

        DB::transaction(function () use ($agency, $admin, $v2006, $v9023, $calbiga, $delaPena, $mabini) {

            // =============================================================
            // VEHICLE 1: SHA-2006 (Rescue Truck - Hino XZU302L)
            // =============================================================
            $this->info('1. Seeding historical logs for SHA-2006 (Rescue Truck)...');

            // 1.1 Repair Log (May 22, 2026 - GSO Motorpool)
            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2006->id,
                'driver_id' => $calbiga->id,
                'repair_date' => '2026-05-22',
                'scope_of_work' => 'Replaced frayed steel wire rope on front hydraulic rescue winch and lubricated heavy-duty 4-way roller fairlead assembly.',
                'parts_replaced' => '12mm x 28m galvanized aircraft-grade winch steel cable, clevis hook with safety latch',
                'cost' => 3450.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Winch load-tested safe at GSO Motorpool grounds under Job Order #CDRRMO-2026-05-019. Ready for emergency winch extraction.',
                'created_at' => '2026-05-22 14:30:00',
                'updated_at' => '2026-05-22 14:30:00',
            ]);

            // 1.2 PM Schedule (Completed on Sept 08, 2026 at 60,020 km)
            DB::table('pm_schedules')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2006->id,
                'service_target' => '60,000 km Major PMS - Heavy-Duty Engine Oil, Oil Filter, & Fuel Filter Replacement',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 55000,
                'due_mileage' => 60000,
                'due_date' => null,
                'due_soon_threshold_km' => 1000,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_COMPLETED,
                'date_serviced' => '2026-09-08',
                'completion_mileage' => 60020,
                'completion_repair_source' => RepairLog::SOURCE_GSO,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => '15W-40 Heavy-duty diesel engine oil (9.5L), OEM oil filter cartridge, primary fuel filter, pre-fuel sedimenter cleaning',
                'completion_remarks' => 'Completed 60,000 km Major PMS at GSO Motorpool. Full fluid replacement and chassis lubrication performed. Vehicle cleared for heavy rescue service.',
                'created_at' => '2026-08-25 09:00:00',
                'updated_at' => '2026-09-08 15:30:00',
            ]);

            // 1.3 Dispatches (3 Trips leading to 60,475 km)
            $dispatches2006 = [
                [
                    'driver_id' => $calbiga->id,
                    'second_driver_id' => $mabini->id,
                    'mission_type' => 'Rescue Operation',
                    'mission_other' => null,
                    'location' => 'CDRRMO Station → Brgy. Hamorawon & Brgy. Nijaga',
                    'date' => '2026-09-16',
                    'time_out' => '09:30:00',
                    'time_in' => '12:30:00',
                    'odo_out' => 60380,
                    'odo_in' => 60415,
                    'remarks' => 'Conducted riverbank visual survey and pre-staged water rescue gear near Nijaga bridge. Normal water level observed.',
                ],
                [
                    'driver_id' => $delaPena->id,
                    'second_driver_id' => null,
                    'mission_type' => 'Rescue Operation',
                    'mission_other' => null,
                    'location' => 'City Hall Compound → Brgy. Dagum Evacuation Center',
                    'date' => '2026-09-25',
                    'time_out' => '13:00:00',
                    'time_in' => '16:00:00',
                    'odo_out' => 60415,
                    'odo_in' => 60448,
                    'remarks' => 'Transported heavy flood barriers, high-capacity submersible pump, and portable generator set to evacuation site.',
                ],
                [
                    'driver_id' => $mabini->id,
                    'second_driver_id' => null,
                    'mission_type' => 'Administrative Travel',
                    'mission_other' => null,
                    'location' => 'CDRRMO Warehouse → Brgy. Obrero Multi-Purpose Hall',
                    'date' => '2026-10-08',
                    'time_out' => '10:00:00',
                    'time_in' => '12:30:00',
                    'odo_out' => 60448,
                    'odo_in' => 60475,
                    'remarks' => 'Hauled emergency simulation kits and incident command tents for barangay disaster preparedness drill. Vehicle returned in good operational order.',
                ],
            ];

            foreach ($dispatches2006 as $dsp) {
                DB::table('dispatches')->insert([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v2006->id,
                    'driver_id' => $dsp['driver_id'],
                    'second_driver_id' => $dsp['second_driver_id'],
                    'mission_type' => $dsp['mission_type'],
                    'mission_other' => $dsp['mission_other'],
                    'location' => $dsp['location'],
                    'time_out' => "{$dsp['date']} {$dsp['time_out']}",
                    'travel_order_path' => null,
                    'odometer_out' => $dsp['odo_out'],
                    'time_in' => "{$dsp['date']} {$dsp['time_in']}",
                    'odometer_in' => $dsp['odo_in'],
                    'return_status' => Vehicle::STATUS_OPERATIONAL,
                    'remarks' => $dsp['remarks'],
                    'created_at' => "{$dsp['date']} {$dsp['time_out']}",
                    'updated_at' => "{$dsp['date']} {$dsp['time_in']}",
                ]);

                // Rule 10: Dispatch Departure & Return Mobile Status Updates for driver(s)
                $assignedDrivers = array_filter([$dsp['driver_id'], $dsp['second_driver_id']]);
                foreach ($assignedDrivers as $driverUid) {
                    // Departure: Dispatched
                    DB::table('notifications')->insert([
                        'agency_id' => $agency->id,
                        'user_id' => $driverUid,
                        'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                        'title' => 'Vehicle Status Updated',
                        'message' => 'SHA-2006 is now Dispatched.',
                        'data' => json_encode([
                            'plate' => 'SHA-2006',
                            'vehicle_id' => $v2006->id,
                            'from_status' => Vehicle::STATUS_OPERATIONAL,
                            'to_status' => Vehicle::STATUS_DISPATCHED,
                            'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        ]),
                        'is_read' => 0,
                        'read_at' => null,
                        'created_at' => "{$dsp['date']} {$dsp['time_out']}",
                        'updated_at' => "{$dsp['date']} {$dsp['time_out']}",
                    ]);

                    // Return: Operational
                    DB::table('notifications')->insert([
                        'agency_id' => $agency->id,
                        'user_id' => $driverUid,
                        'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                        'title' => 'Vehicle Status Updated',
                        'message' => 'SHA-2006 is now Operational.',
                        'data' => json_encode([
                            'plate' => 'SHA-2006',
                            'vehicle_id' => $v2006->id,
                            'from_status' => Vehicle::STATUS_DISPATCHED,
                            'to_status' => Vehicle::STATUS_OPERATIONAL,
                            'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        ]),
                        'is_read' => 0,
                        'read_at' => null,
                        'created_at' => "{$dsp['date']} {$dsp['time_in']}",
                        'updated_at' => "{$dsp['date']} {$dsp['time_in']}",
                    ]);
                }
            }

            // =============================================================
            // VEHICLE 2: AND-9023 (Water Tanker - Hino 300 Series)
            // =============================================================
            $this->info('2. Seeding historical logs for AND-9023 (Water Tanker)...');

            // 2.1 Repair Log (June 18, 2026 - External Repair Shop)
            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v9023->id,
                'driver_id' => $delaPena->id,
                'repair_date' => '2026-06-18',
                'scope_of_work' => 'Overhauled centrifugal water pump discharge valve assembly and replaced leaking high-pressure quick-connect hose coupler gasket seals.',
                'parts_replaced' => '3-inch brass ball valve, neoprene flange packing gaskets (4 pcs), rubber hose clamp bands',
                'cost' => 4800.00,
                'repair_source' => RepairLog::SOURCE_EXTERNAL,
                'external_shop_name' => 'Calbayog Auto Supply & Engineering Services',
                'receipt_path' => null,
                'remarks' => 'Tested water delivery pressure at 8.5 bar without leaks. Discharged auxiliary tank cleanly. Official receipt filed.',
                'created_at' => '2026-06-18 15:15:00',
                'updated_at' => '2026-06-18 15:15:00',
            ]);

            // 2.2 PM Schedule (Due Soon at 65,000 km, current 64,950 km)
            $pmTankerId = DB::table('pm_schedules')->insertGetId([
                'agency_id' => $agency->id,
                'vehicle_id' => $v9023->id,
                'service_target' => '65,000 km Periodic Maintenance Service (PMS) - Transmission & Differential Gear Oil, Brake Fluid Flush, & Pump Pressure Seals',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 60000,
                'due_mileage' => 65000,
                'due_date' => null,
                'due_soon_threshold_km' => 1000,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_DUE_SOON,
                'date_serviced' => null,
                'completion_mileage' => null,
                'completion_repair_source' => null,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => null,
                'completion_remarks' => null,
                'created_at' => '2026-09-10 09:00:00',
                'updated_at' => '2026-10-02 16:30:00',
            ]);

            // 2.3 Admin Notification for PM Due Soon on AND-9023
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_PM_DUE_SOON,
                'title' => 'PM Due Soon',
                'message' => 'AND-9023 reached 64,950 km Due Soon threshold for 65,000 km Periodic Maintenance Service (PMS).',
                'data' => json_encode([
                    'pm_schedule_id' => $pmTankerId,
                    'vehicle_id' => $v9023->id,
                    'plate' => 'AND-9023',
                    'service_target' => '65,000 km Periodic Maintenance Service (PMS) - Transmission & Differential Gear Oil, Brake Fluid Flush, & Pump Pressure Seals',
                    'state' => PmSchedule::STATUS_DUE_SOON,
                ]),
                'is_read' => 0,
                'read_at' => null,
                'created_at' => '2026-10-02 16:30:00',
                'updated_at' => '2026-10-02 16:30:00',
            ]);

            // 2.4 Dispatches (2 Trips leading to 64,950 km)
            $dispatches9023 = [
                [
                    'driver_id' => $delaPena->id,
                    'second_driver_id' => $calbiga->id,
                    'mission_type' => 'Others',
                    'mission_other' => 'Potable Water Distribution',
                    'location' => 'Calbayog Water District Pumping Station → Brgy. Carayman & Brgy. San Policarpo',
                    'date' => '2026-09-18',
                    'time_out' => '10:00:00',
                    'time_in' => '14:00:00',
                    'odo_out' => 64880,
                    'odo_in' => 64918,
                    'remarks' => 'Distributed 10,000 liters of treated drinking water to upland puroks with low water supply pressure. Water pump functioned efficiently.',
                ],
                [
                    'driver_id' => $calbiga->id,
                    'second_driver_id' => null,
                    'mission_type' => 'Others',
                    'mission_other' => 'Emergency Water Supply Standby',
                    'location' => 'CDRRMO Station → Calbayog Sports Center / City Evacuation Ground',
                    'date' => '2026-10-02',
                    'time_out' => '13:30:00',
                    'time_in' => '16:15:00',
                    'odo_out' => 64918,
                    'odo_in' => 64950,
                    'remarks' => 'Stationed water tanker for city evacuation drill sanitation and emergency water supply. Returned fully refilled and operational.',
                ],
            ];

            foreach ($dispatches9023 as $dsp) {
                DB::table('dispatches')->insert([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v9023->id,
                    'driver_id' => $dsp['driver_id'],
                    'second_driver_id' => $dsp['second_driver_id'],
                    'mission_type' => $dsp['mission_type'],
                    'mission_other' => $dsp['mission_other'],
                    'location' => $dsp['location'],
                    'time_out' => "{$dsp['date']} {$dsp['time_out']}",
                    'travel_order_path' => null,
                    'odometer_out' => $dsp['odo_out'],
                    'time_in' => "{$dsp['date']} {$dsp['time_in']}",
                    'odometer_in' => $dsp['odo_in'],
                    'return_status' => Vehicle::STATUS_OPERATIONAL,
                    'remarks' => $dsp['remarks'],
                    'created_at' => "{$dsp['date']} {$dsp['time_out']}",
                    'updated_at' => "{$dsp['date']} {$dsp['time_in']}",
                ]);

                // Rule 10: Dispatch Departure & Return Mobile Status Updates for driver(s)
                $assignedDrivers = array_filter([$dsp['driver_id'], $dsp['second_driver_id']]);
                foreach ($assignedDrivers as $driverUid) {
                    // Departure: Dispatched
                    DB::table('notifications')->insert([
                        'agency_id' => $agency->id,
                        'user_id' => $driverUid,
                        'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                        'title' => 'Vehicle Status Updated',
                        'message' => 'AND-9023 is now Dispatched.',
                        'data' => json_encode([
                            'plate' => 'AND-9023',
                            'vehicle_id' => $v9023->id,
                            'from_status' => Vehicle::STATUS_OPERATIONAL,
                            'to_status' => Vehicle::STATUS_DISPATCHED,
                            'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        ]),
                        'is_read' => 0,
                        'read_at' => null,
                        'created_at' => "{$dsp['date']} {$dsp['time_out']}",
                        'updated_at' => "{$dsp['date']} {$dsp['time_out']}",
                    ]);

                    // Return: Operational
                    DB::table('notifications')->insert([
                        'agency_id' => $agency->id,
                        'user_id' => $driverUid,
                        'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                        'title' => 'Vehicle Status Updated',
                        'message' => 'AND-9023 is now Operational.',
                        'data' => json_encode([
                            'plate' => 'AND-9023',
                            'vehicle_id' => $v9023->id,
                            'from_status' => Vehicle::STATUS_DISPATCHED,
                            'to_status' => Vehicle::STATUS_OPERATIONAL,
                            'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        ]),
                        'is_read' => 0,
                        'read_at' => null,
                        'created_at' => "{$dsp['date']} {$dsp['time_in']}",
                        'updated_at' => "{$dsp['date']} {$dsp['time_in']}",
                    ]);
                }
            }
        });

        $this->info('Successfully seeded authentic historical UAT records for CDRRMO unassigned vehicles (SHA-2006 & AND-9023)!');

        $this->table(
            ['Entity', 'Count'],
            [
                ['Repair Logs (SHA-2006 & AND-9023)', DB::table('repair_logs')->whereIn('vehicle_id', $vehicleIds)->count()],
                ['PM Schedules (SHA-2006 & AND-9023)', DB::table('pm_schedules')->whereIn('vehicle_id', $vehicleIds)->count()],
                ['Dispatches (SHA-2006)', DB::table('dispatches')->where('vehicle_id', $v2006->id)->count()],
                ['Dispatches (AND-9023)', DB::table('dispatches')->where('vehicle_id', $v9023->id)->count()],
                ['Admin PM Alerts', DB::table('notifications')->where('agency_id', $agency->id)->where('user_id', $admin->id)->where('type', Notification::TYPE_PM_DUE_SOON)->count()],
                ['Total Notifications (SHA-2006 & AND-9023)', DB::table('notifications')->where('agency_id', $agency->id)->where(function ($q) {
                    $q->where('message', 'like', '%SHA-2006%')
                      ->orWhere('message', 'like', '%SHA 2006%')
                      ->orWhere('message', 'like', '%AND-9023%')
                      ->orWhere('message', 'like', '%AND 9023%');
                })->count()],
            ]
        );

        return self::SUCCESS;
    }
}

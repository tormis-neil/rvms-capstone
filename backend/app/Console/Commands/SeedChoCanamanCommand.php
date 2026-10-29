<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed authentic historical UAT records specifically for Robert Canaman & SHA 1248 (Ambulance 2).
 *
 * UAT Profile:
 * - Driver Canaman represents an operational driver who was managed predominantly via the web admin console.
 * - 0 Daily BLOWBAGETS Inspections and 0 Damage Reports (clean empty state in mobile app).
 * - Full administrative fleet records: 1 GSO repair log, 2 PM schedules (1 Completed, 1 Upcoming), and 2 dispatches.
 * - Receives 4 dispatch status update notifications on mobile when vehicle departs and returns.
 */
class SeedChoCanamanCommand extends Command
{
    protected $signature = 'rvms:seed-cho-canaman';

    protected $description = 'Seed authentic historical UAT records specifically for Robert Canaman & SHA 1248 (Ambulance 2)';

    public function handle(): int
    {
        $agency = Agency::where('code', 'CHO')->first();
        if (! $agency) {
            $this->error('CHO agency record not found!');
            return self::FAILURE;
        }

        $admin = User::where('agency_id', $agency->id)->where('role', User::ROLE_ADMIN)->first();
        if (! $admin) {
            $this->error('CHO admin user not found!');
            return self::FAILURE;
        }

        $v1248 = Vehicle::where('agency_id', $agency->id)->where('plate_number', 'SHA 1248')->first();
        if (! $v1248) {
            $this->error('Vehicle SHA 1248 not found in database.');
            return self::FAILURE;
        }

        $canaman = User::where('email', 'canaman.cho@email.com')->first();
        if (! $canaman) {
            $this->error('Driver Robert Canaman (canaman.cho@email.com) not found in database.');
            return self::FAILURE;
        }

        $serrato = User::where('email', 'serrato.cho@email.com')->first();
        if (! $serrato) {
            $this->error('Driver Reynaldo Serrato (serrato.cho@email.com) not found in database.');
            return self::FAILURE;
        }

        $this->info('Cleaning up existing SHA 1248 operational logs to ensure idempotency...');

        // Clear previous inspections & items for SHA 1248
        $inspIds = DB::table('inspections')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1248->id)
            ->pluck('id');

        DB::table('inspection_items')->whereIn('inspection_id', $inspIds)->delete();
        DB::table('inspections')->whereIn('id', $inspIds)->delete();

        // Clear previous damage reports for SHA 1248
        DB::table('damage_reports')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1248->id)
            ->delete();

        // Clear previous repair logs for SHA 1248
        DB::table('repair_logs')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1248->id)
            ->delete();

        // Clear previous PM schedules for SHA 1248
        DB::table('pm_schedules')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1248->id)
            ->delete();

        // Clear previous dispatches for SHA 1248
        DB::table('dispatches')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1248->id)
            ->delete();

        // Clear previous notifications for Canaman and admin alerts for SHA 1248 / Canaman
        DB::table('notifications')
            ->where('agency_id', $agency->id)
            ->where('user_id', $canaman->id)
            ->delete();

        DB::table('notifications')
            ->where('agency_id', $agency->id)
            ->where('user_id', $admin->id)
            ->where(function ($q) use ($v1248) {
                $q->where('data', 'like', '%SHA 1248%')
                  ->orWhere('data', 'like', '%Robert Canaman%')
                  ->orWhere('message', 'like', '%SHA 1248%');
            })
            ->delete();

        DB::transaction(function () use ($agency, $admin, $v1248, $canaman, $serrato) {
            // -------------------------------------------------------------
            // 0. ACCOUNT & VEHICLE CREATION TIMESTAMPS
            // Ensure accounts and vehicle exist prior to first operational submission (Sep 17, 2026)
            // -------------------------------------------------------------
            DB::table('users')->where('id', $admin->id)->update([
                'created_at' => '2026-09-14 08:00:00',
            ]);

            DB::table('users')->where('id', $canaman->id)->update([
                'created_at' => '2026-09-14 10:15:00',
            ]);

            DB::table('vehicles')->where('id', $v1248->id)->update([
                'created_at' => '2026-09-14 09:00:00',
            ]);

            // -------------------------------------------------------------
            // 1. REPAIR LOG (1 Historical Repair: Apr 22, 2026, No Document)
            // -------------------------------------------------------------
            $this->info('1. Seeding 1 historical repair log for SHA 1248 (GSO Motorpool)...');

            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1248->id,
                'driver_id' => $canaman->id,
                'repair_date' => '2026-04-22',
                'scope_of_work' => 'Replaced blown 100W emergency roof lightbar flasher fuse and re-secured loose siren speaker bracket bolts after road vibration.',
                'parts_replaced' => '30A automotive blade fuses (2 pcs), heavy-duty zinc mounting bolts with nylon lock nuts',
                'cost' => 450.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Completed by GSO Motorpool electrician under Job Order #CHO-2026-04-019. Roof lightbar flasher and siren bracket secured.',
                'created_at' => '2026-04-22 14:00:00',
                'updated_at' => '2026-04-22 14:00:00',
            ]);

            // -------------------------------------------------------------
            // 2. PREVENTIVE MAINTENANCE SCHEDULES (2 PM Schedules)
            // 1 Completed in June (Mileage-Based, specifies parts)
            // 1 Scheduled as Upcoming (Mileage-Based)
            // -------------------------------------------------------------
            $this->info('2. Seeding 2 PM schedules for SHA 1248 (1 Completed, 1 Upcoming)...');

            // PM 1: Completed on June 18, 2026 (Mileage-Based, 45,000 km)
            DB::table('pm_schedules')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1248->id,
                'service_target' => '45,000 km Periodic Maintenance Service (PMS) - Engine Oil, Fuel Filter, & Front Brake Pads Overhaul',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 40000,
                'due_mileage' => 45000,
                'due_date' => null,
                'due_soon_threshold_km' => 1000,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_COMPLETED,
                'date_serviced' => '2026-06-18',
                'completion_mileage' => 45050,
                'completion_repair_source' => RepairLog::SOURCE_GSO,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => 'Fully synthetic diesel engine oil (7L), OEM oil filter, fuel filter cartridge, front ceramic brake pad set',
                'completion_remarks' => 'Completed 45,000 km PMS at GSO Motorpool. Engine oil replaced, fuel filter renewed, and front brake pads serviced. Unit cleared roadworthy.',
                'created_at' => '2026-06-05 09:00:00',
                'updated_at' => '2026-06-18 15:30:00',
            ]);

            // PM 2: Upcoming (Mileage-Based, 50,000 km)
            DB::table('pm_schedules')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1248->id,
                'service_target' => '50,000 km Major Periodic Maintenance Service (PMS) - Transmission Fluid & Brake System Check',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 45050,
                'due_mileage' => 50000,
                'due_date' => null,
                'due_soon_threshold_km' => 1000,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_UPCOMING,
                'date_serviced' => null,
                'completion_mileage' => null,
                'completion_repair_source' => null,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => null,
                'completion_remarks' => null,
                'created_at' => '2026-06-18 16:00:00',
                'updated_at' => '2026-06-18 16:00:00',
            ]);

            // -------------------------------------------------------------
            // 3. DAILY BLOWBAGETS INSPECTIONS & DAMAGE REPORTS
            // Omitted by realistic design: Driver Canaman tested purely via web admin dispatches.
            // -------------------------------------------------------------
            $this->info('3. Skipping inspections and damage reports for Canaman (pure web admin dispatch profile)...');

            // -------------------------------------------------------------
            // 4. DISPATCHES (2 Dispatches co-crewed with Reynaldo Serrato)
            // 1 Patient Transport under Others + 1 Medical Response
            // Progressing from 47,020 km -> 47,075 km
            // -------------------------------------------------------------
            $this->info('4. Seeding 2 Dispatches for SHA 1248 (Patient Transport + Medical Response)...');

            $dispatches = [
                // Dispatch 1: Patient Transport
                [
                    'mission_type' => Dispatch::MISSION_OTHERS,
                    'mission_other' => 'Patient Transport',
                    'location' => 'Brgy. Rawis to Calbayog District Hospital ER',
                    'date' => '2026-09-17',
                    'time_out' => '10:00:00',
                    'time_in' => '11:30:00',
                    'odo_out' => 47020,
                    'odo_in' => 47048,
                    'remarks' => 'Emergency patient transport of trauma patient from coastal barangay to District Hospital ER. Stretcher and suction handled by crew.',
                ],
                // Dispatch 2: Medical Response
                [
                    'mission_type' => 'Medical Response',
                    'mission_other' => null,
                    'location' => 'Brgy. Dagum Plaza (Community Medical Mission & Triage Station)',
                    'date' => '2026-09-24',
                    'time_out' => '13:45:00',
                    'time_in' => '15:30:00',
                    'odo_out' => 47048,
                    'odo_in' => 47075,
                    'remarks' => 'On-site standby and rapid medical response during CHO community medical outreach. Crew ready with emergency trauma kit.',
                ],
            ];

            foreach ($dispatches as $dsp) {
                DB::table('dispatches')->insert([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v1248->id,
                    'driver_id' => $canaman->id,
                    'second_driver_id' => $serrato->id,
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
            }

            // Sync SHA 1248 vehicle current mileage and operational status
            DB::table('vehicles')
                ->where('id', $v1248->id)
                ->update([
                    'current_mileage' => 47075,
                    'status' => Vehicle::STATUS_OPERATIONAL,
                    'updated_at' => '2026-09-24 15:30:00',
                ]);

            // -------------------------------------------------------------
            // 5. HISTORICAL NOTIFICATIONS (Direct DB Inserts with Exact Past Timestamps)
            // Status updates generated on mobile when admin opens and closes dispatches
            // -------------------------------------------------------------
            $this->info('5. Seeding Dispatch Status Notifications for Robert Canaman...');

            $notifs = [
                // Sep 17, 2026 — Driver Canaman: Dispatched (Trip 1 departure)
                [
                    'user_id' => $canaman->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 1248 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 1248',
                        'vehicle_id' => $v1248->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Patient Transport — Brgy. Trinidad Coastal Health Station to Calbayog District Hospital.',
                    ]),
                    'created_at' => '2026-09-17 10:00:00',
                ],
                // Sep 17, 2026 — Driver Canaman: Returned from Trip 1
                [
                    'user_id' => $canaman->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 1248 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 1248',
                        'vehicle_id' => $v1248->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Emergency patient transport of trauma patient from coastal barangay to District Hospital ER. Stretcher and suction handled by crew.',
                    ]),
                    'created_at' => '2026-09-17 11:30:00',
                ],
                // Sep 24, 2026 — Driver Canaman: Dispatched (Trip 2 departure)
                [
                    'user_id' => $canaman->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 1248 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 1248',
                        'vehicle_id' => $v1248->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Medical Response — Brgy. Dagum Plaza (Community Medical Mission & Triage Station).',
                    ]),
                    'created_at' => '2026-09-24 13:45:00',
                ],
                // Sep 24, 2026 — Driver Canaman: Returned from Trip 2
                [
                    'user_id' => $canaman->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 1248 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 1248',
                        'vehicle_id' => $v1248->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'On-site standby and rapid medical response during CHO community medical outreach. Crew ready with emergency trauma kit.',
                    ]),
                    'created_at' => '2026-09-24 15:30:00',
                ],
            ];

            foreach ($notifs as $n) {
                DB::table('notifications')->insert([
                    'agency_id' => $agency->id,
                    'user_id' => $n['user_id'],
                    'type' => $n['type'],
                    'title' => $n['title'],
                    'message' => $n['message'],
                    'data' => $n['data'],
                    'is_read' => 0,
                    'read_at' => null,
                    'created_at' => $n['created_at'],
                    'updated_at' => $n['created_at'],
                ]);
            }
        });

        $this->info('Successfully seeded authentic historical UAT data for Robert Canaman & SHA 1248!');
        $this->table(
            ['Entity', 'Count for SHA 1248 / Canaman'],
            [
                ['Inspections', DB::table('inspections')->where('vehicle_id', $v1248->id)->count()],
                ['Inspection Items', DB::table('inspection_items')->whereIn('inspection_id', DB::table('inspections')->where('vehicle_id', $v1248->id)->pluck('id'))->count()],
                ['Damage Reports', DB::table('damage_reports')->where('vehicle_id', $v1248->id)->count()],
                ['Repair Logs', DB::table('repair_logs')->where('vehicle_id', $v1248->id)->count()],
                ['PM Schedules', DB::table('pm_schedules')->where('vehicle_id', $v1248->id)->count()],
                ['Dispatches', DB::table('dispatches')->where('vehicle_id', $v1248->id)->count()],
                ['Canaman Notifications', DB::table('notifications')->where('user_id', $canaman->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

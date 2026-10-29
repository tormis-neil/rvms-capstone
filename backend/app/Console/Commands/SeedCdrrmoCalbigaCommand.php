<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\InspectionChecklistItem;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed authentic historical UAT records for Rolando Calbiga & SAB-5120 (Rescue Truck - Isuzu D-Max 4x4).
 *
 * Rules:
 * - Direct DB::table inserts to strictly preserve past created_at timestamps.
 * - Sub-Date Range: Oct 01 to Oct 21, 2026 (weekdays only, 9am - 5pm).
 * - Language: Simple English + authentic Calbayognon Waray (strictly using "san" and "sa", replacing all "han" and "ha").
 * - 1 Damage report (Oct 09) leading to Not Operational status review.
 * - 1 Repair log (Oct 14) at GSO Motorpool (₱4,350) restoring vehicle to Operational.
 * - 1 Completed PM (40k km) and 1 Upcoming Scheduled PM (45k km).
 * - 3 Dispatches calibrated from 41,990 km to current 42,150 km.
 * - 8 Matching mobile alerts in Calbiga's inbox (dispatches, damage downtime, repair completion).
 * - Strictly no dummy attachments (all receipt_path, photo_path are null).
 */
class SeedCdrrmoCalbigaCommand extends Command
{
    protected $signature = 'rvms:seed-cdrrmo-calbiga';

    protected $description = 'Seed authentic historical UAT records for Rolando Calbiga & SAB-5120 (Rescue Truck)';

    public function handle(): int
    {
        $this->info('Starting historical data seeding for Rolando Calbiga & SAB-5120...');

        $agency = Agency::where('code', 'CDRRMO')->first();
        if (! $agency) {
            $this->error('CDRRMO agency not found!');
            return self::FAILURE;
        }

        $admin = User::where('agency_id', $agency->id)
            ->where('email', 'cdrrmo.admin@email.com')
            ->first();

        if (! $admin) {
            $this->error('CDRRMO admin user (cdrrmo.admin@email.com) not found!');
            return self::FAILURE;
        }

        $v5120 = Vehicle::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'SAB-5120')
                  ->orWhere('plate_number', 'SAB 5120');
            })->first();

        if (! $v5120) {
            $this->error('Vehicle SAB-5120 not found in CDRRMO fleet.');
            return self::FAILURE;
        }

        $calbiga = User::where('email', 'calbiga@email.com')->first();
        if (! $calbiga) {
            $this->error('Driver Rolando Calbiga (calbiga@email.com) not found in database.');
            return self::FAILURE;
        }

        $mabini = User::where('email', 'mabini@email.com')->first();
        $delaPena = User::where('email', 'jonathan@email.com')->first();

        $checklists = InspectionChecklistItem::forAgencyCode('CDRRMO')->get();
        if ($checklists->isEmpty()) {
            $this->error('BLOWBAGETS checklist catalog is empty.');
            return self::FAILURE;
        }

        $this->info('Cleaning up existing Calbiga-authored logs on SAB-5120 to ensure idempotency...');

        // Clear Calbiga's inspections on SAB-5120
        $calbigaInspIds = DB::table('inspections')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v5120->id)
            ->where('driver_id', $calbiga->id)
            ->pluck('id');

        DB::table('inspection_items')->whereIn('inspection_id', $calbigaInspIds)->delete();
        DB::table('inspections')->whereIn('id', $calbigaInspIds)->delete();

        // Clear Calbiga's damage reports on SAB-5120
        DB::table('damage_reports')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v5120->id)
            ->where('driver_id', $calbiga->id)
            ->delete();

        // Clear previous dispatches for SAB-5120
        DB::table('dispatches')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v5120->id)
            ->delete();

        // Clear previous PM schedules & repair logs on SAB-5120
        DB::table('repair_logs')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v5120->id)
            ->delete();

        DB::table('pm_schedules')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v5120->id)
            ->delete();

        // Clear Calbiga's notifications & admin alerts regarding SAB-5120
        DB::table('notifications')
            ->where('agency_id', $agency->id)
            ->where('user_id', $calbiga->id)
            ->delete();

        DB::table('notifications')
            ->where('agency_id', $agency->id)
            ->where('user_id', $admin->id)
            ->where(function ($q) use ($v5120) {
                $q->where('data', 'like', '%SAB-5120%')
                  ->orWhere('data', 'like', '%SAB 5120%')
                  ->orWhere('data', 'like', '%Rolando Calbiga%')
                  ->orWhere('message', 'like', '%SAB-5120%')
                  ->orWhere('message', 'like', '%SAB 5120%');
            })
            ->delete();

        DB::transaction(function () use ($agency, $admin, $v5120, $calbiga, $mabini, $delaPena, $checklists) {
            // -------------------------------------------------------------
            // 0. ACCOUNT & VEHICLE CREATION TIMESTAMPS
            // Ensure accounts and vehicle exist prior to first operational submission (Sept 16/Oct 02, 2026)
            // -------------------------------------------------------------
            DB::table('users')->where('id', $admin->id)->update([
                'created_at' => '2026-09-14 08:00:00',
            ]);

            DB::table('users')->where('id', $calbiga->id)->update([
                'created_at' => '2026-09-14 09:15:00',
            ]);

            DB::table('vehicles')->where('id', $v5120->id)->update([
                'created_at' => '2026-09-14 08:45:00',
            ]);

            // -------------------------------------------------------------
            // 1. PREVENTIVE MAINTENANCE SCHEDULES (1 Completed + 1 Upcoming)
            // -------------------------------------------------------------
            $this->info('1. Seeding 2 PM schedules for SAB-5120 (1 Completed 40k, 1 Scheduled 45k)...');

            // 1A. Completed 40,000 km Major PMS
            DB::table('pm_schedules')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v5120->id,
                'service_target' => '40,000 km Major PMS - Heavy-Duty Engine Oil, Transmission & Differential Gear Lube, & Multi-Point Brake Service',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 10000,
                'last_pm_mileage' => 30000,
                'due_mileage' => 40000,
                'due_date' => null,
                'due_soon_threshold_km' => 2000,
                'due_soon_threshold_days' => null,
                'status' => PmSchedule::STATUS_COMPLETED,
                'date_serviced' => '2026-08-14',
                'completion_mileage' => 40050,
                'completion_repair_source' => RepairLog::SOURCE_GSO,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => 'Engine oil (7L 15W-40), OEM oil filter, fuel filter, differential gear oil (SAE 90), DOT 4 brake fluid flush',
                'completion_remarks' => 'Completed full 40,000 km PMS cycle at GSO Motorpool. Multi-point inspection passed for heavy rescue operations.',
                'created_at' => '2026-08-01 09:00:00',
                'updated_at' => '2026-08-14 16:30:00',
            ]);

            // 1B. Upcoming 45,000 km Periodic PMS
            DB::table('pm_schedules')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v5120->id,
                'service_target' => '45,000 km Periodic Maintenance Service (PMS) - Engine Oil & Filter Change, Air Cleaner Element, & Drive Belt Tension Inspection',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 40000,
                'due_mileage' => 45000,
                'due_date' => null,
                'due_soon_threshold_km' => 2000,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_UPCOMING,
                'date_serviced' => null,
                'completion_mileage' => null,
                'completion_repair_source' => null,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => null,
                'completion_remarks' => null,
                'created_at' => '2026-10-01 09:00:00',
                'updated_at' => '2026-10-01 09:00:00',
            ]);

            // -------------------------------------------------------------
            // 2. DAILY BLOWBAGETS INSPECTIONS (5 Checks Authored by Calbiga)
            // -------------------------------------------------------------
            $this->info('2. Seeding 5 daily inspections authored by Rolando Calbiga...');

            $inspections = [
                [
                    'date' => '2026-10-01',
                    'time' => '09:10:00',
                    'review_time' => '11:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Ginsusi an lana san makina, coolant, ngan ligid. Hapsay an dalagan san 4x4 D-Max.',
                ],
                [
                    'date' => '2026-10-06',
                    'time' => '09:15:00',
                    'review_time' => '11:30:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Kompleto an early warning device, tow strap, ngan hydraulic jack sa likod. Waray aberya.',
                ],
                [
                    'date' => '2026-10-08',
                    'time' => '09:20:00',
                    'review_time' => '11:00:00',
                    'flagged_item' => 'Power Steering',
                    'flagged_note' => 'Power Steering: Medyo nag-uuyog an manibela pag-abot sa 60 kph sa may diversion road.',
                    'remarks' => 'Slight vibration observed at high speed. Front wheel alignment scheduled.',
                ],
                // Oct 09-14: Out of service (Damage reported and vehicle under repair)
                [
                    'date' => '2026-10-15',
                    'time' => '09:10:00',
                    'review_time' => '10:45:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Bag-o an ball joint ngan tie rod end tikang sa GSO Motorpool. Hapsay na liwat an pagliko san manibela.',
                ],
                [
                    'date' => '2026-10-21',
                    'time' => '09:25:00',
                    'review_time' => '11:20:00',
                    'flagged_item' => 'Lights',
                    'flagged_note' => 'Lights: Overhead lightbar strobe relay medyo ma-delay pagpitik pero functional an sirena ngan driving headlights.',
                    'remarks' => 'Lightbar strobe relay re-seated tightly. Siren and primary driving lights fully operational.',
                ],
            ];

            foreach ($inspections as $ins) {
                $inspId = DB::table('inspections')->insertGetId([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v5120->id,
                    'driver_id' => $calbiga->id,
                    'inspection_date' => $ins['date'],
                    'review_status' => 'Reviewed',
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => "{$ins['date']} {$ins['review_time']}",
                    'created_at' => "{$ins['date']} {$ins['time']}",
                    'updated_at' => "{$ins['date']} {$ins['review_time']}",
                ]);

                foreach ($checklists as $chk) {
                    $hasIssue = ($ins['flagged_item'] && strcasecmp($chk->name, $ins['flagged_item']) === 0);
                    DB::table('inspection_items')->insert([
                        'inspection_id' => $inspId,
                        'checklist_item_id' => $chk->id,
                        'status' => $hasIssue ? 'Has Issue' : 'OK',
                        'remarks' => $hasIssue ? $ins['flagged_note'] : null,
                    ]);
                }
            }

            // -------------------------------------------------------------
            // 3. DAMAGE REPORT (Oct 09, 2026 - Lower ball joint & tie rod)
            // -------------------------------------------------------------
            $this->info('3. Seeding 1 damage report submitted by Rolando Calbiga...');

            DB::table('damage_reports')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v5120->id,
                'driver_id' => $calbiga->id,
                'nature_of_damage' => 'Nagraragubrob ngan makuri an manibela pagliko sa tuo pag-agi sa libaong ngan baha sa Brgy. Nijaga samtang nagdudul-ong ug rescue equipment. May tulo liwat nga hydraulic fluid sa idarom.',
                'suspected_parts' => 'Front right lower suspension ball joint / outer tie rod end / power steering rack seal kit',
                'photo_path' => null,
                'date_reported' => '2026-10-09',
                'status' => 'Reviewed',
                'reviewed_by' => $admin->id,
                'reviewed_at' => '2026-10-09 11:30:00',
                'created_at' => '2026-10-09 10:15:00',
                'updated_at' => '2026-10-09 11:30:00',
            ]);

            // -------------------------------------------------------------
            // 4. REPAIR LOG (Oct 14, 2026 - GSO Motorpool)
            // -------------------------------------------------------------
            $this->info('4. Seeding 1 repair log for SAB-5120 (GSO Motorpool)...');

            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v5120->id,
                'driver_id' => $calbiga->id,
                'repair_date' => '2026-10-14',
                'scope_of_work' => 'Replaced sheared front right lower suspension ball joint and outer steering tie rod end; renewed high-pressure power steering line seals; performed front-wheel toe-in alignment and road testing.',
                'parts_replaced' => 'Front right lower ball joint assembly, outer tie rod end, power steering high-pressure O-ring kit, Dexron III ATF fluid (2L)',
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'cost' => 4350.00,
                'receipt_path' => null,
                'remarks' => 'Steering stiffness and knocking completely eliminated under Job Order #CDRRMO-2026-10-008. Vehicle certified road-ready for flood and rescue dispatch.',
                'created_at' => '2026-10-14 14:00:00',
                'updated_at' => '2026-10-14 14:00:00',
            ]);

            // -------------------------------------------------------------
            // 5. DISPATCHES (3 Records: Calibrated 41,990 km -> 42,150 km)
            // -------------------------------------------------------------
            $this->info('5. Seeding 3 dispatches for SAB-5120 (Calibrated to 42,150 km)...');

            $dispatches = [
                // Trip 1: Oct 02, 2026 (09:30–12:30) | Odo: 41,990 -> 42,045 km (55 km)
                [
                    'second_driver_id' => $mabini ? $mabini->id : null,
                    'mission_type' => 'Rescue Operation',
                    'mission_other' => null,
                    'location' => 'CDRRMO Base → Brgy. Hamorawon & Brgy. Dagum',
                    'date' => '2026-10-02',
                    'time_out' => '09:30:00',
                    'time_in' => '12:30:00',
                    'odo_out' => 41990,
                    'odo_in' => 42045,
                    'remarks' => 'Conducted rapid riverbank flood stage monitoring and distributed relief kits. Vehicle performed smoothly.',
                ],
                // Trip 2: Oct 07, 2026 (13:00–16:30) | Odo: 42,045 -> 42,105 km (60 km)
                [
                    'second_driver_id' => null,
                    'mission_type' => 'Rescue Operation',
                    'mission_other' => null,
                    'location' => 'City Hall Compound → Brgy. Tinambacan Emergency Outpost',
                    'date' => '2026-10-07',
                    'time_out' => '13:00:00',
                    'time_in' => '16:30:00',
                    'odo_out' => 42045,
                    'odo_in' => 42105,
                    'remarks' => 'Delivered heavy-duty water rescue generator and inflatable boat rigging supplies to emergency sub-station.',
                ],
                // Trip 3: Oct 19, 2026 (10:00–13:15) | Odo: 42,105 -> 42,150 km (45 km) - Post-repair
                [
                    'second_driver_id' => $delaPena ? $delaPena->id : null,
                    'mission_type' => 'Rescue Operation',
                    'mission_other' => null,
                    'location' => 'CDRRMO Base → Brgy. Obrero & Brgy. Carayman (Flood Monitoring)',
                    'date' => '2026-10-19',
                    'time_out' => '10:00:00',
                    'time_in' => '13:15:00',
                    'odo_out' => 42105,
                    'odo_in' => 42150,
                    'remarks' => 'Post-repair field inspection and coastal storm surge gauge verification. Steering response flawless.',
                ],
            ];

            foreach ($dispatches as $dsp) {
                DB::table('dispatches')->insert([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v5120->id,
                    'driver_id' => $calbiga->id,
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
            }

            // Sync Vehicle final state
            DB::table('vehicles')
                ->where('id', $v5120->id)
                ->update([
                    'current_mileage' => 42150,
                    'status' => Vehicle::STATUS_OPERATIONAL,
                    'status_source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                    'status_changed_at' => '2026-10-19 13:15:00',
                    'updated_at' => '2026-10-19 13:15:00',
                ]);

            // -------------------------------------------------------------
            // 6. HISTORICAL NOTIFICATIONS (Calbiga & Admin)
            // -------------------------------------------------------------
            $this->info('6. Seeding Chronological Notifications for Rolando Calbiga & Admin...');

            $notifs = [
                // Oct 02, 2026 — Driver Calbiga: Dispatched (Trip 1)
                [
                    'user_id' => $calbiga->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SAB-5120 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SAB-5120',
                        'vehicle_id' => $v5120->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Rescue Operation — CDRRMO Base to Brgy. Hamorawon & Brgy. Dagum.',
                    ]),
                    'created_at' => '2026-10-02 09:30:00',
                ],
                // Oct 02, 2026 — Driver Calbiga: Returned from Trip 1
                [
                    'user_id' => $calbiga->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SAB-5120 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SAB-5120',
                        'vehicle_id' => $v5120->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Conducted rapid riverbank flood stage monitoring and distributed relief kits. Vehicle performed smoothly.',
                    ]),
                    'created_at' => '2026-10-02 12:30:00',
                ],
                // Oct 07, 2026 — Driver Calbiga: Dispatched (Trip 2)
                [
                    'user_id' => $calbiga->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SAB-5120 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SAB-5120',
                        'vehicle_id' => $v5120->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Rescue Operation — City Hall Compound to Brgy. Tinambacan Emergency Outpost.',
                    ]),
                    'created_at' => '2026-10-07 13:00:00',
                ],
                // Oct 07, 2026 — Driver Calbiga: Returned from Trip 2
                [
                    'user_id' => $calbiga->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SAB-5120 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SAB-5120',
                        'vehicle_id' => $v5120->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Delivered heavy-duty water rescue generator and inflatable boat rigging supplies to emergency sub-station.',
                    ]),
                    'created_at' => '2026-10-07 16:30:00',
                ],
                // Oct 08, 2026 — Admin: Flagged inspection on SAB-5120 (Steering)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'SAB-5120 — 1 item flagged: Power Steering (steering vibration noted by Calbiga).',
                    'data' => json_encode(['vehicle_id' => $v5120->id, 'plate_number' => 'SAB-5120', 'driver' => 'Rolando Calbiga']),
                    'created_at' => '2026-10-08 09:20:00',
                ],
                // Oct 09, 2026 — Admin: New Damage Report Submitted by Calbiga
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                    'title' => 'New Damage Report Submitted',
                    'message' => 'Rolando Calbiga reported steering stiffness and fluid leak on SAB-5120.',
                    'data' => json_encode(['vehicle_id' => $v5120->id, 'plate_number' => 'SAB-5120', 'driver' => 'Rolando Calbiga']),
                    'created_at' => '2026-10-09 10:15:00',
                ],
                // Oct 09, 2026 — Driver Calbiga: Status update to Not Operational
                [
                    'user_id' => $calbiga->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SAB-5120 is now Not Operational.',
                    'data' => json_encode([
                        'plate' => 'SAB-5120',
                        'vehicle_id' => $v5120->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_NOT_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DAMAGE,
                        'remarks' => 'Grounded for GSO Motorpool repair due to sheared front right lower ball joint, tie rod play, and power steering fluid leak.',
                    ]),
                    'created_at' => '2026-10-09 11:30:00',
                ],
                // Oct 14, 2026 — Driver Calbiga: Status restored to Operational after repair
                [
                    'user_id' => $calbiga->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SAB-5120 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SAB-5120',
                        'vehicle_id' => $v5120->id,
                        'from_status' => Vehicle::STATUS_NOT_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_REPAIR,
                        'remarks' => 'Steering stiffness and knocking completely eliminated under Job Order #CDRRMO-2026-10-008. Vehicle certified road-ready.',
                    ]),
                    'created_at' => '2026-10-14 14:00:00',
                ],
                // Oct 19, 2026 — Driver Calbiga: Dispatched (Trip 3)
                [
                    'user_id' => $calbiga->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SAB-5120 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SAB-5120',
                        'vehicle_id' => $v5120->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Rescue Operation — CDRRMO Base to Brgy. Obrero & Brgy. Carayman (Flood Monitoring).',
                    ]),
                    'created_at' => '2026-10-19 10:00:00',
                ],
                // Oct 19, 2026 — Driver Calbiga: Returned from Trip 3
                [
                    'user_id' => $calbiga->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SAB-5120 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SAB-5120',
                        'vehicle_id' => $v5120->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Post-repair field inspection and coastal storm surge gauge verification. Steering response flawless.',
                    ]),
                    'created_at' => '2026-10-19 13:15:00',
                ],
                // Oct 21, 2026 — Admin: Flagged inspection on SAB-5120 (Lights)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'SAB-5120 — 1 item flagged: Lights (overhead strobe relay delay noted).',
                    'data' => json_encode(['vehicle_id' => $v5120->id, 'plate_number' => 'SAB-5120', 'driver' => 'Rolando Calbiga']),
                    'created_at' => '2026-10-21 09:25:00',
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

        $this->info('Successfully seeded authentic historical UAT records for Rolando Calbiga & SAB-5120!');

        $this->table(
            ['Entity', 'Count for SAB-5120 / Calbiga'],
            [
                ['Inspections', DB::table('inspections')->where('vehicle_id', $v5120->id)->where('driver_id', $calbiga->id)->count()],
                ['Inspection Items', DB::table('inspection_items')->whereIn('inspection_id', DB::table('inspections')->where('vehicle_id', $v5120->id)->pluck('id'))->count()],
                ['Damage Reports', DB::table('damage_reports')->where('vehicle_id', $v5120->id)->count()],
                ['Repair Logs', DB::table('repair_logs')->where('vehicle_id', $v5120->id)->count()],
                ['PM Schedules', DB::table('pm_schedules')->where('vehicle_id', $v5120->id)->count()],
                ['Dispatches', DB::table('dispatches')->where('vehicle_id', $v5120->id)->count()],
                ['Calbiga Notifications', DB::table('notifications')->where('user_id', $calbiga->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

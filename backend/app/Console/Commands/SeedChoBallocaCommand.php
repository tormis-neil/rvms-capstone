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
 * Seed authentic, realistic historical UAT data specifically for Idelfonso Balloca & SHA 2625 (CHO).
 *
 * Rules:
 * - Direct DB::table inserts to strictly preserve past created_at timestamps.
 * - Dates: June 2026 (repairs) through Oct 14, 2026 (weekdays only, 9am - 5pm).
 * - Language: Simple English + authentic Calbayognon Waray-waray.
 * - Preserves all users and vehicle inventory, wipes only operational logs on --fresh.
 */
class SeedChoBallocaCommand extends Command
{
    protected $signature = 'rvms:seed-cho-balloca {--fresh : Wipe previous CHO operational logs before seeding}';

    protected $description = 'Seed authentic historical UAT records specifically for Idelfonso Balloca & SHA 2625';

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

        $v2625 = Vehicle::where('agency_id', $agency->id)->where('plate_number', 'SHA 2625')->first();
        if (! $v2625) {
            $this->error('Vehicle SHA 2625 not found in database.');
            return self::FAILURE;
        }

        $balloca = User::where('email', 'balloca.cho@email.com')->first();
        $gamao   = User::where('email', 'gamao.cho@email.com')->first();

        if (! $balloca || ! $gamao) {
            $this->error('Driver Balloca or Gamao not found in database.');
            return self::FAILURE;
        }

        $checklists = InspectionChecklistItem::forAgencyCode('CHO')->get();
        if ($checklists->isEmpty()) {
            $this->error('BLOWBAGETS checklist catalog is empty.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->info('Cleaning up all CHO operational history (inspections, damages, repairs, PMs, dispatches, notifications)...');
            $this->info('Preserving all user accounts and vehicle records.');

            // Delete all inspection items and inspections for CHO
            $choInspIds = DB::table('inspections')->where('agency_id', $agency->id)->pluck('id');
            DB::table('inspection_items')->whereIn('inspection_id', $choInspIds)->delete();
            DB::table('inspections')->where('agency_id', $agency->id)->delete();

            // Delete all damage reports for CHO
            DB::table('damage_reports')->where('agency_id', $agency->id)->delete();

            // Delete all repair logs for CHO
            DB::table('repair_logs')->where('agency_id', $agency->id)->delete();

            // Delete all PM schedules for CHO
            DB::table('pm_schedules')->where('agency_id', $agency->id)->delete();

            // Delete all dispatches for CHO
            DB::table('dispatches')->where('agency_id', $agency->id)->delete();

            // Delete all notifications for CHO
            DB::table('notifications')->where('agency_id', $agency->id)->delete();

            $this->info('All previous CHO operational logs cleared. Ready to seed only Balloca data.');
        } else {
            // Clean up previous Balloca-specific records to ensure idempotency
            $this->info('Cleaning up any existing Balloca-authored operational logs to ensure idempotency...');
            $ballocaInspIds = DB::table('inspections')
                ->where('agency_id', $agency->id)
                ->where('driver_id', $balloca->id)
                ->pluck('id');
            DB::table('inspection_items')->whereIn('inspection_id', $ballocaInspIds)->delete();
            DB::table('inspections')->whereIn('id', $ballocaInspIds)->delete();

            DB::table('damage_reports')
                ->where('agency_id', $agency->id)
                ->where('driver_id', $balloca->id)
                ->delete();

            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $balloca->id)
                ->delete();

            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $admin->id)
                ->where('data', 'like', '%Idelfonso Balloca%')
                ->delete();
        }

        DB::transaction(function () use ($agency, $admin, $v2625, $balloca, $gamao, $checklists) {
            // -------------------------------------------------------------
            // 1. REPAIR LOGS (2 Distinct Repairs on SHA 2625)
            // -------------------------------------------------------------
            $this->info('1. Seeding 2 distinct historical repair logs for SHA 2625...');

            // Repair 1: Jun 16, 2026 — GSO Motorpool (Suspension & Steering)
            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2625->id,
                'driver_id' => $balloca->id,
                'repair_date' => '2026-06-16',
                'scope_of_work' => 'Replaced worn front stabilizer link bushings and conducted front-end wheel alignment after reported clunking over uneven rural roads.',
                'parts_replaced' => 'Front stabilizer link bushings (4 pcs), steering tie rod end (right)',
                'cost' => 2650.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Work completed by GSO Motorpool under Job Order #CHO-2026-06-033. Steering play eliminated and test driven safe.',
                'created_at' => '2026-06-16 14:30:00',
                'updated_at' => '2026-06-16 14:30:00',
            ]);

            // Repair 2: Aug 28, 2026 — External Repair Shop (Drive belt & Caliper pins)
            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2625->id,
                'driver_id' => $balloca->id,
                'repair_date' => '2026-08-28',
                'scope_of_work' => 'Replaced squealing alternator/fan serpentine drive belt and serviced front brake caliper slider pins with synthetic high-temp grease.',
                'parts_replaced' => 'Bando 7PK Serpentine Drive Belt, front brake caliper slider pins & dust boots',
                'cost' => 3800.00,
                'repair_source' => RepairLog::SOURCE_EXTERNAL,
                'external_shop_name' => 'Calbayog Auto Supply & Repair Center',
                'receipt_path' => null,
                'remarks' => 'Belt slippage eliminated during heavy electrical load and night driving. Brakes inspected with 65% remaining pad life. Official receipt filed.',
                'created_at' => '2026-08-28 15:45:00',
                'updated_at' => '2026-08-28 15:45:00',
            ]);

            // -------------------------------------------------------------
            // 2. PREVENTIVE MAINTENANCE SCHEDULE (1 PM Schedule for SHA 2625)
            // -------------------------------------------------------------
            $this->info('2. Seeding 1 PM schedule for SHA 2625 (Due Soon status)...');

            $pmId = DB::table('pm_schedules')->insertGetId([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2625->id,
                'service_target' => '60,000 km Major Preventive Maintenance Service (PMS)',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 55000,
                'due_mileage' => 60000,
                'due_date' => null,
                'due_soon_threshold_km' => 2000,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_DUE_SOON,
                'date_serviced' => null,
                'completion_mileage' => null,
                'completion_repair_source' => null,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => null,
                'completion_remarks' => null,
                'created_at' => '2026-09-14 09:30:00',
                'updated_at' => '2026-10-05 08:30:00',
            ]);

            // -------------------------------------------------------------
            // 3. DAILY BLOWBAGETS INSPECTIONS (Submitted by Idelfonso Balloca)
            // -------------------------------------------------------------
            $this->info('3. Seeding 5 daily inspections by Idelfonso Balloca...');

            $inspections = [
                [
                    'date' => '2026-09-17',
                    'time' => '09:15:00',
                    'review_time' => '11:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Cargo bed cleared. Checked 4x4, tire pressure, and engine oil. Ready for field outreach.',
                ],
                [
                    'date' => '2026-09-24',
                    'time' => '09:30:00',
                    'review_time' => '11:45:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Maupay an dalagan san makina. Complete safety tools, jack, and spare tire in place.',
                ],
                [
                    'date' => '2026-10-02',
                    'time' => '09:20:00',
                    'review_time' => '11:00:00',
                    'flagged_item' => 'Lights',
                    'flagged_note' => 'Lights: Medyo malubay an suga ha likod (left tail light). Gin-hugtan ko an socket nag-suga na liwat.',
                    'remarks' => 'Tail light socket connection slightly loose, secured and working. All fluids ok.',
                ],
                [
                    'date' => '2026-10-07',
                    'time' => '09:10:00',
                    'review_time' => '10:30:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Good condition an service pickup. Checked radiator coolant and brake fluid, functional tanan.',
                ],
                [
                    'date' => '2026-10-14',
                    'time' => '09:15:00',
                    'review_time' => '10:45:00',
                    'flagged_item' => 'Engine',
                    'flagged_note' => 'Medyo humok an clutch pedal ngan makuri isulod an primera kambio labi na kon mainit makina.',
                    'remarks' => 'Referred clutch stiffness and soft pedal to admin for GSO Motorpool inspection.',
                ],
            ];

            foreach ($inspections as $ins) {
                $inspId = DB::table('inspections')->insertGetId([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v2625->id,
                    'driver_id' => $balloca->id,
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
            // 4. DAMAGE REPORT (Submitted by Idelfonso Balloca)
            // -------------------------------------------------------------
            $this->info('4. Seeding Damage Report submitted by Idelfonso Balloca...');

            DB::table('damage_reports')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2625->id,
                'driver_id' => $balloca->id,
                'nature_of_damage' => 'Medyo nag-iislayd an clutch pag tungas sa bukid san Brgy. Obrero kanina samtang naghahatud ug medical supplies. Makuri na isulod an primera ngan segunda kambio.',
                'suspected_parts' => 'Clutch pressure plate / clutch disc wear or release bearing',
                'photo_path' => null,
                'date_reported' => '2026-10-12',
                'status' => 'Reviewed',
                'reviewed_by' => $admin->id,
                'reviewed_at' => '2026-10-13 09:00:00',
                'created_at' => '2026-10-12 11:15:00',
                'updated_at' => '2026-10-13 09:00:00',
            ]);

            // -------------------------------------------------------------
            // 5. DISPATCHES (Crewed by Balloca, leading to 58,400 km)
            // -------------------------------------------------------------
            $this->info('5. Seeding 4 Dispatches for SHA 2625...');

            $dispatches = [
                [
                    'location' => 'Brgy. San Policarpo Health Station',
                    'date' => '2026-09-17',
                    'time_out' => '09:30:00',
                    'time_in' => '12:00:00',
                    'odo_out' => 58280,
                    'odo_in' => 58315,
                    'remarks' => 'Delivered monthly vaccine supplies and cold-chain containers to barangay health workers.',
                ],
                [
                    'location' => 'Brgy. Dagum & Brgy. Matobato Health Centers',
                    'date' => '2026-09-24',
                    'time_out' => '13:00:00',
                    'time_in' => '16:00:00',
                    'odo_out' => 58315,
                    'odo_in' => 58350,
                    'remarks' => 'Transported Dengue vector control misting equipment and larvicide chemicals.',
                ],
                [
                    'location' => 'City Hall Motorpool & GSO Supply Office',
                    'date' => '2026-10-02',
                    'time_out' => '10:00:00',
                    'time_in' => '11:45:00',
                    'odo_out' => 58350,
                    'odo_in' => 58372,
                    'remarks' => 'Hauled emergency medical supply boxes and health record logbooks from City Hall.',
                ],
                [
                    'location' => 'Brgy. Obrero Rural Health Unit',
                    'date' => '2026-10-07',
                    'time_out' => '09:30:00',
                    'time_in' => '12:30:00',
                    'odo_out' => 58372,
                    'odo_in' => 58400,
                    'remarks' => 'Delivered rural health maternal kits and nutrition supplies to upland health center.',
                ],
            ];

            foreach ($dispatches as $dsp) {
                DB::table('dispatches')->insert([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v2625->id,
                    'driver_id' => $balloca->id,
                    'second_driver_id' => $gamao->id,
                    'mission_type' => 'Administrative Travel',
                    'mission_other' => null,
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

            // -------------------------------------------------------------
            // 6. HISTORICAL NOTIFICATIONS (Direct DB Inserts with Exact Past Timestamps)
            // -------------------------------------------------------------
            $this->info('6. Seeding Chronological Notifications for Balloca, Gamao, and Admin...');

            $notifs = [
                // Sep 17, 2026 — Driver Balloca: Dispatched (Trip 1)
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                    ]),
                    'created_at' => '2026-09-17 09:30:00',
                ],
                // Sep 17, 2026 — Driver Balloca: Returned from Trip 1
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                    ]),
                    'created_at' => '2026-09-17 12:00:00',
                ],
                // Sep 24, 2026 — Driver Balloca: Dispatched (Trip 2)
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                    ]),
                    'created_at' => '2026-09-24 13:00:00',
                ],
                // Sep 24, 2026 — Driver Balloca: Returned from Trip 2
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                    ]),
                    'created_at' => '2026-09-24 16:00:00',
                ],
                // Oct 02, 2026 — Admin: Flagged inspection on SHA 2625 (Lights)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'SHA 2625 — 1 item flagged: Lights (tail light socket connection secured).',
                    'data' => json_encode(['vehicle_id' => $v2625->id, 'plate_number' => 'SHA 2625', 'driver' => 'Idelfonso Balloca']),
                    'created_at' => '2026-10-02 09:20:00',
                ],
                // Oct 02, 2026 — Driver Balloca: Dispatched (Trip 3)
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                    ]),
                    'created_at' => '2026-10-02 10:00:00',
                ],
                // Oct 02, 2026 — Driver Balloca: Returned from Trip 3
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                    ]),
                    'created_at' => '2026-10-02 11:45:00',
                ],
                // Oct 05, 2026 — Driver Balloca: PM Reminder Due Soon
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_PM_REMINDER,
                    'title' => 'Preventive Maintenance',
                    'message' => 'SHA 2625 — 60,000 km Major Preventive Maintenance Service (PMS) is due soon.',
                    'data' => json_encode([
                        'pm_schedule_id' => $pmId,
                        'vehicle_id' => $v2625->id,
                        'plate' => 'SHA 2625',
                        'service_target' => '60,000 km Major Preventive Maintenance Service (PMS)',
                        'state' => 'Due Soon',
                    ]),
                    'created_at' => '2026-10-05 08:30:00',
                ],
                // Oct 05, 2026 — Admin: PM Due Soon Alert on SHA 2625
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_PM_DUE_SOON,
                    'title' => 'PM Due Soon',
                    'message' => 'SHA 2625 — 60,000 km Major Preventive Maintenance Service (PMS) is due soon.',
                    'data' => json_encode([
                        'pm_schedule_id' => $pmId,
                        'vehicle_id' => $v2625->id,
                        'plate' => 'SHA 2625',
                        'service_target' => '60,000 km Major Preventive Maintenance Service (PMS)',
                        'state' => 'Due Soon',
                    ]),
                    'created_at' => '2026-10-05 08:30:00',
                ],
                // Oct 07, 2026 — Driver Balloca: Dispatched (Trip 4)
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                    ]),
                    'created_at' => '2026-10-07 09:30:00',
                ],
                // Oct 07, 2026 — Driver Balloca: Returned from Trip 4
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                    ]),
                    'created_at' => '2026-10-07 12:30:00',
                ],
                // Oct 12, 2026 — Admin: New Damage Report Submitted by Balloca
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                    'title' => 'New Damage Report Submitted',
                    'message' => 'Idelfonso Balloca reported clutch slipping on SHA 2625.',
                    'data' => json_encode(['vehicle_id' => $v2625->id, 'plate_number' => 'SHA 2625', 'driver' => 'Idelfonso Balloca']),
                    'created_at' => '2026-10-12 11:15:00',
                ],
                // Oct 13, 2026 — Driver Balloca: Status update to Not Operational
                [
                    'user_id' => $balloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Not Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_NOT_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DAMAGE,
                    ]),
                    'created_at' => '2026-10-13 09:00:00',
                ],
                // Oct 13, 2026 — Driver Gamao (Primary): Status update parity to Not Operational
                [
                    'user_id' => $gamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Not Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_NOT_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DAMAGE,
                    ]),
                    'created_at' => '2026-10-13 09:00:00',
                ],
                // Oct 14, 2026 — Admin: Flagged inspection on SHA 2625 (Clutch pedal note)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'SHA 2625 — 1 item flagged: Engine (clutch stiffness reported prior to motorpool entry).',
                    'data' => json_encode(['vehicle_id' => $v2625->id, 'plate_number' => 'SHA 2625', 'driver' => 'Idelfonso Balloca']),
                    'created_at' => '2026-10-14 09:15:00',
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

        $this->info('Successfully seeded authentic historical UAT data for Idelfonso Balloca & SHA 2625!');
        $this->table(
            ['Entity', 'Count for SHA 2625 / Balloca'],
            [
                ['Inspections', DB::table('inspections')->where('vehicle_id', $v2625->id)->count()],
                ['Inspection Items', DB::table('inspection_items')->whereIn('inspection_id', DB::table('inspections')->where('vehicle_id', $v2625->id)->pluck('id'))->count()],
                ['Damage Reports', DB::table('damage_reports')->where('vehicle_id', $v2625->id)->count()],
                ['Repair Logs', DB::table('repair_logs')->where('vehicle_id', $v2625->id)->count()],
                ['PM Schedules', DB::table('pm_schedules')->where('vehicle_id', $v2625->id)->count()],
                ['Dispatches', DB::table('dispatches')->where('vehicle_id', $v2625->id)->count()],
                ['Balloca Notifications', DB::table('notifications')->where('user_id', $balloca->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

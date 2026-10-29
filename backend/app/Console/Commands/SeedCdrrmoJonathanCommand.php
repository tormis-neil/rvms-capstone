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
 * Seed authentic historical UAT records for Jonathan Dela Peña & CHO-4467 (Patient Transport Vehicle - Toyota Coaster).
 *
 * Rules:
 * - Direct DB::table inserts to strictly preserve past created_at timestamps.
 * - Sub-Date Range: Sept 15 to Oct 09, 2026 (weekdays only, 9am - 5pm).
 * - Language: Simple English + authentic Calbayognon Waray (strictly using "san" and "sa", replacing all "han" and "ha").
 * - Co-crewed with Christopher Clark Mabini as second driver on dispatches.
 * - Calibrated odometers lead up to current 53,820 km.
 * - Standardized PM notification title: 'Preventive Maintenance'.
 * - Preserves all users, vehicles, and existing records for other vehicles/drivers.
 */
class SeedCdrrmoJonathanCommand extends Command
{
    protected $signature = 'rvms:seed-cdrrmo-jonathan {--fresh : Wipe previously seeded CDRRMO UAT data before seeding}';

    protected $description = 'Seed authentic historical UAT records for Jonathan Dela Peña & CHO-4467 (Patient Transport Vehicle)';

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

        $v4467 = Vehicle::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'CHO-4467')
                  ->orWhere('plate_number', 'CHO 4467');
            })->first();

        if (! $v4467) {
            $this->error('Vehicle CHO-4467 not found in CDRRMO fleet.');
            return self::FAILURE;
        }

        $delaPena = User::where('email', 'jonathan@email.com')->first();
        if (! $delaPena) {
            $this->error('Driver Jonathan Dela Peña (jonathan@email.com) not found in database.');
            return self::FAILURE;
        }

        $mabini = User::where('email', 'mabini@email.com')->first();
        if (! $mabini) {
            $this->error('Driver Christopher Clark Mabini (mabini@email.com) not found in database.');
            return self::FAILURE;
        }

        $checklists = InspectionChecklistItem::forAgencyCode('CDRRMO')->get();
        if ($checklists->isEmpty()) {
            $this->error('BLOWBAGETS checklist catalog is empty.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->info('Cleaning up all CDRRMO operational history (inspections, damages, repairs, PMs, dispatches, notifications)...');
            $this->info('Preserving all user accounts and vehicle records.');

            // Delete all inspection items and inspections for CDRRMO
            $cdrrmoInspIds = DB::table('inspections')->where('agency_id', $agency->id)->pluck('id');
            DB::table('inspection_items')->whereIn('inspection_id', $cdrrmoInspIds)->delete();
            DB::table('inspections')->where('agency_id', $agency->id)->delete();

            // Delete all damage reports for CDRRMO
            DB::table('damage_reports')->where('agency_id', $agency->id)->delete();

            // Delete all repair logs for CDRRMO
            DB::table('repair_logs')->where('agency_id', $agency->id)->delete();

            // Delete all PM schedules for CDRRMO
            DB::table('pm_schedules')->where('agency_id', $agency->id)->delete();

            // Delete all dispatches for CDRRMO
            DB::table('dispatches')->where('agency_id', $agency->id)->delete();

            // Delete all notifications for CDRRMO
            DB::table('notifications')->where('agency_id', $agency->id)->delete();

            $this->info('All previous CDRRMO operational logs cleared. Ready to seed only Jonathan data.');
        } else {
            $this->info('Cleaning up existing Jonathan-authored logs on CHO-4467 to ensure idempotency...');

            // Clear Jonathan's inspections
            $jonathanInspIds = DB::table('inspections')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $v4467->id)
                ->where('driver_id', $delaPena->id)
                ->pluck('id');

            DB::table('inspection_items')->whereIn('inspection_id', $jonathanInspIds)->delete();
            DB::table('inspections')->whereIn('id', $jonathanInspIds)->delete();

            // Clear Jonathan's damage reports
            DB::table('damage_reports')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $v4467->id)
                ->where('driver_id', $delaPena->id)
                ->delete();

            // Clear previous dispatches authored by Jonathan
            DB::table('dispatches')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $v4467->id)
                ->where('driver_id', $delaPena->id)
                ->delete();

            // Clear previous PM schedules & repair logs on CHO-4467
            DB::table('repair_logs')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $v4467->id)
                ->delete();

            DB::table('pm_schedules')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $v4467->id)
                ->delete();

            // Clear Jonathan notifications & admin alerts regarding Jonathan / CHO-4467
            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $delaPena->id)
                ->delete();

            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $admin->id)
                ->where(function ($q) use ($v4467) {
                    $q->where('data', 'like', '%CHO-4467%')
                      ->orWhere('data', 'like', '%CHO 4467%')
                      ->orWhere('data', 'like', '%Jonathan Dela Peña%')
                      ->orWhere('message', 'like', '%CHO-4467%')
                      ->orWhere('message', 'like', '%CHO 4467%');
                })
                ->delete();
        }

        DB::transaction(function () use ($agency, $admin, $v4467, $delaPena, $mabini, $checklists) {
            // -------------------------------------------------------------
            // 0. ACCOUNT & VEHICLE CREATION TIMESTAMPS
            // Ensure accounts and vehicle exist prior to first operational submission (Sep 15, 2026)
            // -------------------------------------------------------------
            DB::table('users')->where('id', $admin->id)->update([
                'created_at' => '2026-09-14 08:00:00',
            ]);

            DB::table('users')->where('id', $delaPena->id)->update([
                'created_at' => '2026-09-14 09:00:00',
            ]);

            DB::table('users')->where('id', $mabini->id)->update([
                'created_at' => '2026-09-14 09:30:00',
            ]);

            DB::table('vehicles')->where('id', $v4467->id)->update([
                'created_at' => '2026-09-14 08:30:00',
            ]);

            // -------------------------------------------------------------
            // 1. REPAIR LOG (June 10, 2026 - GSO Motorpool)
            // -------------------------------------------------------------
            $this->info('1. Seeding 1 historical repair log for CHO-4467 (GSO Motorpool)...');

            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v4467->id,
                'driver_id' => $delaPena->id,
                'repair_date' => '2026-06-10',
                'scope_of_work' => 'Adjusted dual air-over-hydraulic brake booster clearances, bled pneumatic brake lines, and renewed front passenger pneumatic folding door emergency pressure release valve.',
                'parts_replaced' => 'Air brake booster seal repair kit, DOT 4 heavy-duty brake fluid (2L), pneumatic door valve O-rings',
                'cost' => 3200.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Dual-circuit air brake pressure build-up verified within standard 7.5 bar. Passenger entrance door smooth operation confirmed under Job Order #CDRRMO-2026-06-015.',
                'created_at' => '2026-06-10 14:00:00',
                'updated_at' => '2026-06-10 14:00:00',
            ]);

            // -------------------------------------------------------------
            // 2. PREVENTIVE MAINTENANCE SCHEDULE (55,000 km PMS - Due Soon)
            // -------------------------------------------------------------
            $this->info('2. Seeding 1 PM schedule for CHO-4467 (Due Soon status)...');

            $pmId = DB::table('pm_schedules')->insertGetId([
                'agency_id' => $agency->id,
                'vehicle_id' => $v4467->id,
                'service_target' => '55,000 km Periodic Maintenance Service (PMS) - Dual Air Filter Cleaning, Injector Calibration, & Clutch Fluid Service',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 50000,
                'due_mileage' => 55000,
                'due_date' => null,
                'due_soon_threshold_km' => 1500,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_DUE_SOON,
                'date_serviced' => null,
                'completion_mileage' => null,
                'completion_repair_source' => null,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => null,
                'completion_remarks' => null,
                'created_at' => '2026-09-15 09:00:00',
                'updated_at' => '2026-10-05 08:30:00',
            ]);

            // -------------------------------------------------------------
            // 3. DAILY BLOWBAGETS INSPECTIONS (5 Checks Authored by Jonathan)
            // -------------------------------------------------------------
            $this->info('3. Seeding 5 daily inspections authored by Jonathan Dela Peña...');

            $inspections = [
                [
                    'date' => '2026-09-15',
                    'time' => '09:10:00',
                    'review_time' => '11:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Ginsusi an air brake pressure gauge ngan lana san makina. Maupay an bugnaw san dual aircon.',
                ],
                [
                    'date' => '2026-09-21',
                    'time' => '09:20:00',
                    'review_time' => '11:30:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Hapsay an dalagan san Coaster. Kompleto an first-aid trauma kit ngan stretcher tie-downs sa luyo.',
                ],
                [
                    'date' => '2026-09-29',
                    'time' => '09:15:00',
                    'review_time' => '11:00:00',
                    'flagged_item' => 'Lights',
                    'flagged_note' => 'Lights: Medyo malubay an suga san overhead passenger cabin dome light sa may ikalima nga row. Pero an tanan nga headlight ngan signal lights functional.',
                    'remarks' => 'Overhead cabin dome light socket re-seated and illuminated properly. Main driving lights clear.',
                ],
                [
                    'date' => '2026-10-05',
                    'time' => '09:25:00',
                    'review_time' => '10:45:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Ginsusi an ligid (mga 55 psi) ngan radiator coolant reservoir. Andam an sakyanan para sa mass evacuation standby.',
                ],
                [
                    'date' => '2026-10-09',
                    'time' => '09:30:00',
                    'review_time' => '11:20:00',
                    'flagged_item' => 'Air',
                    'flagged_note' => 'Air: Medyo mahinay an pag-alsa san air pressure gauge sa dashboard kon bag-o pa paandaron an makina.',
                    'remarks' => 'Pneumatic air pressure builds to 7.2 bar after warm up. Monitored for GSO routine check.',
                ],
            ];

            foreach ($inspections as $ins) {
                $inspId = DB::table('inspections')->insertGetId([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v4467->id,
                    'driver_id' => $delaPena->id,
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
            // 4. DAMAGE REPORT (Passenger Door Actuator Linkage)
            // -------------------------------------------------------------
            $this->info('4. Seeding 1 damage report submitted by Jonathan Dela Peña...');

            DB::table('damage_reports')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v4467->id,
                'driver_id' => $delaPena->id,
                'nature_of_damage' => 'Medyo nagtitiktik an automatic passenger folding door pagbuklad samtang nagpapasakay ug mga evacuee sa Brgy. Matobato. Makuri ig-lock san motor kon waray manual nga suporta.',
                'suspected_parts' => 'Passenger pneumatic door actuator arm linkage / magnetic door lock switch',
                'photo_path' => null,
                'date_reported' => '2026-10-02',
                'status' => 'Reviewed',
                'reviewed_by' => $admin->id,
                'reviewed_at' => '2026-10-02 14:30:00',
                'created_at' => '2026-10-02 11:00:00',
                'updated_at' => '2026-10-02 14:30:00',
            ]);

            // -------------------------------------------------------------
            // 5. DISPATCHES (3 Trips Leading to 53,820 km)
            // -------------------------------------------------------------
            $this->info('5. Seeding 3 dispatches for CHO-4467 (Crewed by Dela Peña & Mabini)...');

            $dispatches = [
                [
                    'second_driver_id' => $mabini->id,
                    'mission_type' => 'Rescue Operation',
                    'mission_other' => null,
                    'location' => 'CDRRMO Compound → Brgy. Tinambacan Multi-Purpose Evacuation Center',
                    'date' => '2026-09-17',
                    'time_out' => '09:30:00',
                    'time_in' => '13:30:00',
                    'odo_out' => 53680,
                    'odo_in' => 53745,
                    'remarks' => 'Transported 24 elderly and vulnerable evacuees during preemptive coastal storm surge evacuation drill.',
                ],
                [
                    'second_driver_id' => $mabini->id,
                    'mission_type' => 'Medical Response',
                    'mission_other' => null,
                    'location' => 'Calbayog District Hospital → City Sports Complex Medical Staging Area',
                    'date' => '2026-09-24',
                    'time_out' => '13:00:00',
                    'time_in' => '16:00:00',
                    'odo_out' => 53745,
                    'odo_in' => 53785,
                    'remarks' => 'Conveyed outpatient medical staff and disaster response trauma team with mobility supplies.',
                ],
                [
                    'second_driver_id' => null,
                    'mission_type' => 'Administrative Travel',
                    'mission_other' => null,
                    'location' => 'CDRRMO Station → LGU City Hall Annex & Brgy. Obrero',
                    'date' => '2026-10-06',
                    'time_out' => '10:00:00',
                    'time_in' => '13:00:00',
                    'odo_out' => 53785,
                    'odo_in' => 53820,
                    'remarks' => 'Ferried inter-agency disaster preparedness coordination officers and training manuals. Vehicle returned in good operational order.',
                ],
            ];

            foreach ($dispatches as $dsp) {
                DB::table('dispatches')->insert([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v4467->id,
                    'driver_id' => $delaPena->id,
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

            // -------------------------------------------------------------
            // 6. HISTORICAL NOTIFICATIONS (Jonathan & Admin)
            // -------------------------------------------------------------
            $this->info('6. Seeding Chronological Notifications for Jonathan Dela Peña & Admin...');

            $notifs = [
                // Sep 17, 2026 — Driver Jonathan: Dispatched (Trip 1 to Brgy. Tinambacan)
                [
                    'user_id' => $delaPena->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'CHO-4467 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'CHO-4467',
                        'vehicle_id' => $v4467->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Rescue Operation — CDRRMO Compound to Brgy. Tinambacan Multi-Purpose Evacuation Center.',
                    ]),
                    'created_at' => '2026-09-17 09:30:00',
                ],
                // Sep 17, 2026 — Driver Jonathan: Returned from Trip 1
                [
                    'user_id' => $delaPena->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'CHO-4467 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'CHO-4467',
                        'vehicle_id' => $v4467->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Transported 24 elderly and vulnerable evacuees during preemptive coastal storm surge evacuation drill.',
                    ]),
                    'created_at' => '2026-09-17 13:30:00',
                ],
                // Sep 24, 2026 — Driver Jonathan: Dispatched (Trip 2 to City Sports Complex)
                [
                    'user_id' => $delaPena->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'CHO-4467 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'CHO-4467',
                        'vehicle_id' => $v4467->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Medical Response — Calbayog District Hospital to City Sports Complex Medical Staging Area.',
                    ]),
                    'created_at' => '2026-09-24 13:00:00',
                ],
                // Sep 24, 2026 — Driver Jonathan: Returned from Trip 2
                [
                    'user_id' => $delaPena->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'CHO-4467 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'CHO-4467',
                        'vehicle_id' => $v4467->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Conveyed outpatient medical staff and disaster response trauma team with mobility supplies.',
                    ]),
                    'created_at' => '2026-09-24 16:00:00',
                ],
                // Sep 29, 2026 — Admin: Flagged inspection on CHO-4467 (Lights)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'CHO-4467 — 1 item flagged: Lights (passenger cabin dome light socket re-seated).',
                    'data' => json_encode(['vehicle_id' => $v4467->id, 'plate_number' => 'CHO-4467', 'driver' => 'Jonathan Dela Peña']),
                    'created_at' => '2026-09-29 09:15:00',
                ],
                // Oct 02, 2026 — Admin: New Damage Report Submitted by Jonathan
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                    'title' => 'New Damage Report Submitted',
                    'message' => 'Jonathan Dela Peña reported folding door actuator difficulty on CHO-4467.',
                    'data' => json_encode(['vehicle_id' => $v4467->id, 'plate_number' => 'CHO-4467', 'driver' => 'Jonathan Dela Peña']),
                    'created_at' => '2026-10-02 11:00:00',
                ],
                // Oct 05, 2026 — Driver Jonathan: PM Reminder (Standardized title 'Preventive Maintenance')
                [
                    'user_id' => $delaPena->id,
                    'type' => Notification::TYPE_PM_REMINDER,
                    'title' => 'Preventive Maintenance',
                    'message' => 'CHO-4467 — 55,000 km Periodic Maintenance Service (PMS) is due soon.',
                    'data' => json_encode([
                        'pm_schedule_id' => $pmId,
                        'vehicle_id' => $v4467->id,
                        'plate' => 'CHO-4467',
                        'service_target' => '55,000 km Periodic Maintenance Service (PMS) - Dual Air Filter Cleaning, Injector Calibration, & Clutch Fluid Service',
                        'state' => 'Due Soon',
                    ]),
                    'created_at' => '2026-10-05 08:30:00',
                ],
                // Oct 05, 2026 — Admin: PM Due Soon Alert on CHO-4467
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_PM_DUE_SOON,
                    'title' => 'PM Due Soon',
                    'message' => 'CHO-4467 reached 53,820 km Due Soon threshold for 55,000 km Periodic Maintenance Service (PMS).',
                    'data' => json_encode([
                        'pm_schedule_id' => $pmId,
                        'vehicle_id' => $v4467->id,
                        'plate' => 'CHO-4467',
                        'service_target' => '55,000 km Periodic Maintenance Service (PMS) - Dual Air Filter Cleaning, Injector Calibration, & Clutch Fluid Service',
                        'state' => PmSchedule::STATUS_DUE_SOON,
                    ]),
                    'created_at' => '2026-10-05 08:30:00',
                ],
                // Oct 06, 2026 — Driver Jonathan: Dispatched (Trip 3 to City Hall Annex)
                [
                    'user_id' => $delaPena->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'CHO-4467 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'CHO-4467',
                        'vehicle_id' => $v4467->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Administrative Travel — CDRRMO Station to LGU City Hall Annex & Brgy. Obrero.',
                    ]),
                    'created_at' => '2026-10-06 10:00:00',
                ],
                // Oct 06, 2026 — Driver Jonathan: Returned from Trip 3
                [
                    'user_id' => $delaPena->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'CHO-4467 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'CHO-4467',
                        'vehicle_id' => $v4467->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Ferried inter-agency disaster preparedness coordination officers and training manuals. Vehicle returned in good operational order.',
                    ]),
                    'created_at' => '2026-10-06 13:00:00',
                ],
                // Oct 09, 2026 — Admin: Flagged inspection on CHO-4467 (Air)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'CHO-4467 — 1 item flagged: Air (pneumatic air pressure slow buildup observed).',
                    'data' => json_encode(['vehicle_id' => $v4467->id, 'plate_number' => 'CHO-4467', 'driver' => 'Jonathan Dela Peña']),
                    'created_at' => '2026-10-09 09:30:00',
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

        $this->info('Successfully seeded authentic historical UAT records for Jonathan Dela Peña & CHO-4467!');

        $this->table(
            ['Entity', 'Count for CHO-4467 / Jonathan'],
            [
                ['Inspections', DB::table('inspections')->where('vehicle_id', $v4467->id)->where('driver_id', $delaPena->id)->count()],
                ['Inspection Items', DB::table('inspection_items')->whereIn('inspection_id', DB::table('inspections')->where('vehicle_id', $v4467->id)->pluck('id'))->count()],
                ['Damage Reports', DB::table('damage_reports')->where('vehicle_id', $v4467->id)->count()],
                ['Repair Logs', DB::table('repair_logs')->where('vehicle_id', $v4467->id)->count()],
                ['PM Schedules', DB::table('pm_schedules')->where('vehicle_id', $v4467->id)->count()],
                ['Dispatches', DB::table('dispatches')->where('vehicle_id', $v4467->id)->count()],
                ['Jonathan Notifications', DB::table('notifications')->where('user_id', $delaPena->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

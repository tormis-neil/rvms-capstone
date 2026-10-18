<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\InspectionChecklistItem;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedBfpEspinaCommand extends Command
{
    protected $signature = 'rvms:seed-bfp-espina {--fresh : Wipe previously seeded BFP operational history before seeding}';

    protected $description = 'Seed authentic historical UAT records for Alexander Kris Espina & assigned fire truck (RCA-908, BFP)';

    public function handle(): int
    {
        $this->info('Starting historical UAT seeding for Alexander Kris Espina & RCA-908 (BFP)...');

        $agency = Agency::where('code', 'BFP')->first();
        if (! $agency) {
            $this->error('BFP agency record not found!');
            return self::FAILURE;
        }

        $admin = User::where('email', 'bfp@email.com')->first()
            ?? User::where('agency_id', $agency->id)->where('role', User::ROLE_ADMIN)->first();

        if (! $admin) {
            $this->info('BFP admin user not found; creating bfp@email.com...');
            $admin = User::create([
                'agency_id' => $agency->id,
                'role' => User::ROLE_ADMIN,
                'name' => 'BFP Administrator',
                'email' => 'bfp@email.com',
                'password' => 'password',
                'status' => User::STATUS_ACTIVE,
                'created_at' => '2026-09-14 08:00:00',
                'updated_at' => '2026-09-14 08:00:00',
            ]);
        } else {
            if ($admin->email !== 'bfp@email.com') {
                $admin->update(['email' => 'bfp@email.com']);
            }
        }

        $espina = User::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('email', 'espina@email.com')
                  ->orWhere('name', 'like', '%Espina%');
            })->first();

        if (! $espina) {
            $this->info('Driver Alexander Kris Espina not found; creating user account...');
            $espina = User::create([
                'agency_id' => $agency->id,
                'role' => User::ROLE_DRIVER,
                'name' => 'Alexander Kris Espina',
                'email' => 'espina@email.com',
                'password' => 'password',
                'status' => User::STATUS_ACTIVE,
                'license_number' => 'BO9-90-25552',
                'license_expiry_date' => '2027-03-12',
                'nc_ii_number' => null,
                'nc_ii_expiry_date' => null,
                'created_at' => '2026-09-14 08:30:00',
                'updated_at' => '2026-09-14 08:30:00',
            ]);
        } else {
            $espina->update([
                'name' => 'Alexander Kris Espina',
                'email' => 'espina@email.com',
                'license_number' => 'BO9-90-25552',
                'license_expiry_date' => '2027-03-12',
                'nc_ii_number' => null,
                'nc_ii_expiry_date' => null,
            ]);
        }

        $vFireTruck = Vehicle::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'RCA-908')
                  ->orWhere('plate_number', 'RCA 908');
            })->first();

        if (! $vFireTruck) {
            $vFireTruck = Vehicle::where('agency_id', $agency->id)
                ->where('assigned_driver_id', $espina->id)
                ->first();
        }

        if (! $vFireTruck) {
            $this->info('Assigned vehicle RCA-908 not found; creating RCA-908 (Isuzu FTR 850 Fire Truck)...');
            $vFireTruck = Vehicle::create([
                'agency_id' => $agency->id,
                'plate_number' => 'RCA-908',
                'type' => 'Fire Truck',
                'make' => 'Isuzu',
                'model' => 'FTR 850',
                'engine_number' => '4HK1-TC-587234',
                'chassis_number' => 'JALC4W14697100345',
                'current_mileage' => 45230,
                'status' => Vehicle::STATUS_OPERATIONAL,
                'assigned_driver_id' => $espina->id,
                'created_at' => '2026-09-14 08:30:00',
                'updated_at' => '2026-09-14 08:30:00',
            ]);
        } else {
            $vFireTruck->update([
                'plate_number' => 'RCA-908',
                'type' => 'Fire Truck',
                'assigned_driver_id' => $espina->id,
                'status' => Vehicle::STATUS_OPERATIONAL,
            ]);
        }

        $checklists = InspectionChecklistItem::forAgencyCode('BFP')->get();
        if ($checklists->isEmpty()) {
            $this->error('BLOWBAGETS checklist catalog is empty.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->info('Cleaning up all BFP operational history for fresh seeding...');
            $bfpInspIds = DB::table('inspections')->where('agency_id', $agency->id)->pluck('id');
            DB::table('inspection_items')->whereIn('inspection_id', $bfpInspIds)->delete();
            DB::table('inspections')->where('agency_id', $agency->id)->delete();
            DB::table('damage_reports')->where('agency_id', $agency->id)->delete();
            DB::table('repair_logs')->where('agency_id', $agency->id)->delete();
            DB::table('pm_schedules')->where('agency_id', $agency->id)->delete();
            DB::table('dispatches')->where('agency_id', $agency->id)->delete();
            DB::table('notifications')->where('agency_id', $agency->id)->delete();
        } else {
            $this->info('Cleaning up existing Espina-authored logs on assigned vehicle to ensure idempotency...');
            $espinaInspIds = DB::table('inspections')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vFireTruck->id)
                ->where('driver_id', $espina->id)
                ->pluck('id');

            DB::table('inspection_items')->whereIn('inspection_id', $espinaInspIds)->delete();
            DB::table('inspections')->whereIn('id', $espinaInspIds)->delete();

            DB::table('damage_reports')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vFireTruck->id)
                ->where('driver_id', $espina->id)
                ->delete();

            DB::table('pm_schedules')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vFireTruck->id)
                ->delete();

            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $espina->id)
                ->delete();

            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $admin->id)
                ->where(function ($q) use ($vFireTruck) {
                    $q->where('data', 'like', "%{$vFireTruck->plate_number}%")
                      ->orWhere('message', 'like', "%{$vFireTruck->plate_number}%")
                      ->orWhere('data', 'like', '%Espina%');
                })
                ->delete();
        }

        DB::transaction(function () use ($agency, $admin, $vFireTruck, $espina, $checklists) {
            // -------------------------------------------------------------
            // 0. ACCOUNT & VEHICLE CREATION TIMESTAMPS (Rule 11)
            // -------------------------------------------------------------
            DB::table('users')->where('id', $admin->id)->update([
                'created_at' => '2026-09-14 08:00:00',
            ]);

            DB::table('users')->where('id', $espina->id)->update([
                'created_at' => '2026-09-14 08:30:00',
            ]);

            DB::table('vehicles')->where('id', $vFireTruck->id)->update([
                'created_at' => '2026-09-14 08:30:00',
            ]);

            // -------------------------------------------------------------
            // 1. PREVENTIVE MAINTENANCE SCHEDULE (1 Time-Based: Due Soon)
            // -------------------------------------------------------------
            $this->info("1. Seeding 1 PM schedule for {$vFireTruck->plate_number} (Due Soon)...");

            $pmDueSoonId = DB::table('pm_schedules')->insertGetId([
                'agency_id' => $agency->id,
                'vehicle_id' => $vFireTruck->id,
                'service_target' => 'Semi-Annual Fire Pump, Priming Unit & Power Take-Off (PTO) Transmission Inspection',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_TIME,
                'interval_km' => null,
                'last_pm_mileage' => null,
                'due_mileage' => null,
                'due_date' => '2026-10-28',
                'due_soon_threshold_km' => null,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_DUE_SOON,
                'date_serviced' => null,
                'completion_mileage' => null,
                'completion_repair_source' => null,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => null,
                'completion_remarks' => null,
                'created_at' => '2026-09-14 09:00:00',
                'updated_at' => '2026-10-14 08:00:00',
            ]);

            // -------------------------------------------------------------
            // 2. DAILY BLOWBAGETS INSPECTIONS (Sep 25 - Oct 15, 2026)
            // Authored by Alexander Kris Espina across all 14 BFP items
            // -------------------------------------------------------------
            $this->info("2. Seeding 5 daily BLOWBAGETS inspections for {$vFireTruck->plate_number}...");

            $inspections = [
                // Inspection 1: Sep 25, 2026 (Friday) - All OK
                [
                    'date' => '2026-09-25',
                    'time' => '08:15:00',
                    'review_time' => '10:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Ginsusi an battery level, hydraulic PTO engagement, ngan fire pump discharge valves. Hapsay ngan andam an fire truck sa emergency turn-out.',
                ],
                // Inspection 2: Sep 30, 2026 (Wednesday) - All OK
                [
                    'date' => '2026-09-30',
                    'time' => '08:20:00',
                    'review_time' => '10:30:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Normal an presyon san ligid, gumagana an emergency siren ngan beacon lights. Waray nakit-an nga tulo san lana o tubig.',
                ],
                // Inspection 3: Oct 05, 2026 (Monday) - Flagged: Lights (Flasher bracket)
                [
                    'date' => '2026-10-05',
                    'time' => '08:30:00',
                    'review_time' => '10:45:00',
                    'flagged_item' => 'Lights',
                    'flagged_note' => 'Maluag an mounting bracket san rooftop rotating siren flasher samtang gin-testing.',
                    'remarks' => 'May gutiay nga kaluag sa flasher bracket sa atop pero functional la gihapon an mga suga. Nagsunod hin damage report para ma-tighten.',
                ],
                // Inspection 4: Oct 09, 2026 (Friday) - All OK
                [
                    'date' => '2026-10-09',
                    'time' => '08:15:00',
                    'review_time' => '10:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Gin-re-seat ngan gin-tighten an mounting bolts san flasher. Fully operational an tanan nga BLOWBAGETS checklist items.',
                ],
                // Inspection 5: Oct 15, 2026 (Thursday) - All OK
                [
                    'date' => '2026-10-15',
                    'time' => '08:25:00',
                    'review_time' => '10:30:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Kumpleto an karga san tubig sa tangke, hapsay an preno ngan steering, andam para sa sunod nga shift turnover.',
                ],
            ];

            foreach ($inspections as $ins) {
                $inspId = DB::table('inspections')->insertGetId([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $vFireTruck->id,
                    'driver_id' => $espina->id,
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
            // 3. DAMAGE REPORT (Submitted by Alexander Kris Espina on Oct 05, 2026)
            // Non-grounding: Loose beacon flasher mounting bracket
            // -------------------------------------------------------------
            $this->info("3. Seeding 1 minor damage report for {$vFireTruck->plate_number} (Oct 05, 2026)...");

            DB::table('damage_reports')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $vFireTruck->id,
                'driver_id' => $espina->id,
                'nature_of_damage' => 'Maluag an mounting clip san rotating siren flasher, pero nagana la gihapon an suga ngan sirena.',
                'suspected_parts' => 'Rooftop beacon flasher mounting bracket / rubber vibration damper',
                'photo_path' => null,
                'date_reported' => '2026-10-05',
                'status' => 'Reviewed',
                'reviewed_by' => $admin->id,
                'reviewed_at' => '2026-10-05 11:00:00',
                'created_at' => '2026-10-05 09:15:00',
                'updated_at' => '2026-10-05 11:00:00',
            ]);

            // -------------------------------------------------------------
            // 4. NOTIFICATIONS (PM Reminder & Damage Alert)
            // -------------------------------------------------------------
            $this->info('4. Seeding notifications for Alexander Kris Espina & Admin...');

            // Oct 05: Admin Flagged Inspection Alert
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_INSPECTION_FLAGGED,
                'title' => 'Inspection Reported an Issue',
                'message' => "{$vFireTruck->plate_number} — 1 item flagged: Lights (siren flasher bracket loose noted by Espina).",
                'data' => json_encode(['vehicle_id' => $vFireTruck->id, 'plate_number' => $vFireTruck->plate_number, 'driver' => 'Alexander Kris Espina']),
                'is_read' => 1,
                'read_at' => '2026-10-05 10:45:00',
                'created_at' => '2026-10-05 08:30:00',
                'updated_at' => '2026-10-05 10:45:00',
            ]);

            // Oct 05: Admin Damage Report Alert
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                'title' => 'New Damage Report Submitted',
                'message' => "Alexander Kris Espina reported loose flasher clip on {$vFireTruck->plate_number}.",
                'data' => json_encode(['vehicle_id' => $vFireTruck->id, 'plate_number' => $vFireTruck->plate_number, 'driver' => 'Alexander Kris Espina']),
                'is_read' => 1,
                'read_at' => '2026-10-05 11:00:00',
                'created_at' => '2026-10-05 09:15:00',
                'updated_at' => '2026-10-05 11:00:00',
            ]);

            // Oct 14: Driver Espina PM Due Soon Alert
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $espina->id,
                'type' => Notification::TYPE_PM_REMINDER,
                'title' => 'Preventive Maintenance',
                'message' => "{$vFireTruck->plate_number} — Semi-Annual Fire Pump, Priming Unit & PTO Transmission Inspection is due soon.",
                'data' => json_encode([
                    'pm_schedule_id' => $pmDueSoonId,
                    'vehicle_id' => $vFireTruck->id,
                    'plate' => $vFireTruck->plate_number,
                    'service_target' => 'Semi-Annual Fire Pump, Priming Unit & Power Take-Off (PTO) Transmission Inspection',
                    'state' => 'Due Soon',
                ]),
                'is_read' => 0,
                'read_at' => null,
                'created_at' => '2026-10-14 08:00:00',
                'updated_at' => '2026-10-14 08:00:00',
            ]);

            // Oct 14: Admin PM Due Soon Alert
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_PM_REMINDER,
                'title' => 'Preventive Maintenance',
                'message' => "{$vFireTruck->plate_number} — Semi-Annual Fire Pump, Priming Unit & PTO Transmission Inspection is due soon.",
                'data' => json_encode([
                    'pm_schedule_id' => $pmDueSoonId,
                    'vehicle_id' => $vFireTruck->id,
                    'plate' => $vFireTruck->plate_number,
                    'service_target' => 'Semi-Annual Fire Pump, Priming Unit & Power Take-Off (PTO) Transmission Inspection',
                    'state' => 'Due Soon',
                ]),
                'is_read' => 0,
                'read_at' => null,
                'created_at' => '2026-10-14 08:00:00',
                'updated_at' => '2026-10-14 08:00:00',
            ]);

            // Ensure RCA-908 vehicle record is updated
            DB::table('vehicles')->where('id', $vFireTruck->id)->update([
                'current_mileage' => 45230,
                'status' => Vehicle::STATUS_OPERATIONAL,
                'updated_at' => '2026-10-15 08:25:00',
            ]);
        });

        $this->info('Alexander Kris Espina & RCA-908 UAT seeding completed successfully.');
        return self::SUCCESS;
    }
}

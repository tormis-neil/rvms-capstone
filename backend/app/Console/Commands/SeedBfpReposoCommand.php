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

class SeedBfpReposoCommand extends Command
{
    protected $signature = 'rvms:seed-bfp-reposo {--fresh : Wipe previously seeded Reposo operational history before seeding}';

    protected $description = 'Seed authentic historical UAT records for John Erol Reposo & assigned water tanker (EFG-4532, BFP)';

    public function handle(): int
    {
        $this->info('Starting historical UAT seeding for John Erol Reposo & EFG-4532 (BFP)...');

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
        }

        $reposo = User::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('email', 'reposo@email.com')
                  ->orWhere('name', 'like', '%Reposo%');
            })->first();

        if (! $reposo) {
            $this->info('Driver John Erol Reposo not found; creating user account...');
            $reposo = User::create([
                'agency_id' => $agency->id,
                'role' => User::ROLE_DRIVER,
                'name' => 'John Erol Reposo',
                'email' => 'reposo@email.com',
                'password' => 'password',
                'status' => User::STATUS_ACTIVE,
                'license_number' => 'N08-23-90884',
                'license_expiry_date' => '2028-10-26',
                'nc_ii_number' => 'DRV-2022-01145',
                'nc_ii_expiry_date' => '2027-06-13',
                'created_at' => '2026-09-14 08:45:00',
                'updated_at' => '2026-09-14 08:45:00',
            ]);
        } else {
            $reposo->update([
                'name' => 'John Erol Reposo',
                'email' => 'reposo@email.com',
                'license_number' => 'N08-23-90884',
                'license_expiry_date' => '2028-10-26',
                'nc_ii_number' => 'DRV-2022-01145',
                'nc_ii_expiry_date' => '2027-06-13',
            ]);
        }

        $vTanker = Vehicle::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'EFG-4532')
                  ->orWhere('plate_number', 'EFG 4532');
            })->first();

        if (! $vTanker) {
            $vTanker = Vehicle::where('agency_id', $agency->id)
                ->where('assigned_driver_id', $reposo->id)
                ->first();
        }

        if (! $vTanker) {
            $this->info('Assigned vehicle EFG-4532 not found; creating EFG-4532 (Isuzu FVR Water Tanker)...');
            $vTanker = Vehicle::create([
                'agency_id' => $agency->id,
                'plate_number' => 'EFG-4532',
                'type' => 'Water Tanker',
                'make' => 'Isuzu',
                'model' => 'FVR',
                'engine_number' => '6HK1-XS-778812',
                'chassis_number' => 'JALFVR34LC7000891',
                'current_mileage' => 81650,
                'status' => Vehicle::STATUS_OPERATIONAL,
                'assigned_driver_id' => $reposo->id,
                'created_at' => '2026-09-14 08:45:00',
                'updated_at' => '2026-09-14 08:45:00',
            ]);
        } else {
            $vTanker->update([
                'plate_number' => 'EFG-4532',
                'type' => 'Water Tanker',
                'assigned_driver_id' => $reposo->id,
                'status' => Vehicle::STATUS_OPERATIONAL,
            ]);
        }

        $checklists = InspectionChecklistItem::forAgencyCode('BFP')->get();
        if ($checklists->isEmpty()) {
            $this->error('BLOWBAGETS checklist catalog is empty.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->info('Cleaning up existing Reposo logs on EFG-4532 for fresh seeding...');
            $reposoInspIds = DB::table('inspections')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vTanker->id)
                ->where('driver_id', $reposo->id)
                ->pluck('id');

            DB::table('inspection_items')->whereIn('inspection_id', $reposoInspIds)->delete();
            DB::table('inspections')->whereIn('id', $reposoInspIds)->delete();

            DB::table('damage_reports')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vTanker->id)
                ->where('driver_id', $reposo->id)
                ->delete();

            DB::table('repair_logs')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vTanker->id)
                ->delete();

            DB::table('pm_schedules')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vTanker->id)
                ->delete();

            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $reposo->id)
                ->delete();

            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $admin->id)
                ->where(function ($q) use ($vTanker) {
                    $q->where('data', 'like', "%{$vTanker->plate_number}%")
                      ->orWhere('message', 'like', "%{$vTanker->plate_number}%")
                      ->orWhere('data', 'like', '%Reposo%');
                })
                ->delete();
        }

        DB::transaction(function () use ($agency, $admin, $vTanker, $reposo, $checklists) {
            // -------------------------------------------------------------
            // 0. ACCOUNT & VEHICLE CREATION TIMESTAMPS (Rule 11)
            // -------------------------------------------------------------
            DB::table('users')->where('id', $admin->id)->update([
                'created_at' => '2026-09-14 08:00:00',
            ]);

            DB::table('users')->where('id', $reposo->id)->update([
                'created_at' => '2026-09-14 08:45:00',
            ]);

            DB::table('vehicles')->where('id', $vTanker->id)->update([
                'created_at' => '2026-09-14 08:45:00',
            ]);

            // -------------------------------------------------------------
            // 1. PREVENTIVE MAINTENANCE SCHEDULES (1 Completed, 1 Upcoming)
            // -------------------------------------------------------------
            $this->info("1. Seeding PM schedules for {$vTanker->plate_number} (1 Completed, 1 Upcoming)...");

            // PM 1: Completed on Sep 28, 2026 at 81,650 km
            DB::table('pm_schedules')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $vTanker->id,
                'service_target' => '80,000 km Heavy Chassis Lubrication, Tank Mountings & Brake System Servicing',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 75000,
                'due_mileage' => 80000,
                'due_date' => null,
                'due_soon_threshold_km' => 1000,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_COMPLETED,
                'date_serviced' => '2026-09-28',
                'completion_mileage' => 81650,
                'completion_repair_source' => RepairLog::SOURCE_GSO,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => 'Heavy gear oil 85W-140 (4L), chassis multi-purpose grease, air brake diaphragm kit, primary fuel strainer',
                'completion_remarks' => 'Completed 80,000 km preventive maintenance at GSO Motorpool under Job Order #BFP-2026-09-024. Water tanker suspension mountings torqued to specification.',
                'created_at' => '2026-09-14 09:30:00',
                'updated_at' => '2026-09-28 15:30:00',
            ]);

            // PM 2: Next Upcoming Cycle (85,000 km PMS)
            DB::table('pm_schedules')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $vTanker->id,
                'service_target' => '85,000 km Periodic Maintenance Service (PMS) - Engine Oil, Cooling System Flush & Water Pump Seals',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 81650,
                'due_mileage' => 86650,
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
                'created_at' => '2026-09-28 16:00:00',
                'updated_at' => '2026-09-28 16:00:00',
            ]);

            // -------------------------------------------------------------
            // 2. DAILY BLOWBAGETS INSPECTIONS (Sep 26 - Oct 14, 2026)
            // Authored by John Erol Reposo across all 14 BFP items
            // -------------------------------------------------------------
            $this->info("2. Seeding 5 daily BLOWBAGETS inspections for {$vTanker->plate_number}...");

            $inspections = [
                // Inspection 1: Sep 26, 2026 (Saturday) - All OK
                [
                    'date' => '2026-09-26',
                    'time' => '08:30:00',
                    'review_time' => '10:30:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Ginsusi an tangke san tubig, pressure relief valves, ngan preno. Maupay an dalagan san makina ngan waray tulo.',
                ],
                // Inspection 2: Sep 29, 2026 (Tuesday) - All OK
                [
                    'date' => '2026-09-29',
                    'time' => '08:15:00',
                    'review_time' => '10:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Bag-o gin-PMS san GSO. Marigon an mounting bolts san water tank ngan makusog an preno.',
                ],
                // Inspection 3: Oct 02, 2026 (Friday) - Flagged: Hydraulic System / Discharge Valve Leak
                [
                    'date' => '2026-10-02',
                    'time' => '08:45:00',
                    'review_time' => '10:00:00',
                    'flagged_item' => 'Hydraulic System',
                    'flagged_note' => 'May tulo san discharge gate valve sa may luyo, nag-aawas an tubig.',
                    'remarks' => 'Guba an gasket ngan valve seat san auxiliary rear discharge manifold. Diri ma-hold an pressure, ginpa-ground para ma-repair.',
                ],
                // Inspection 4: Oct 08, 2026 (Thursday) - All OK (Post-Repair)
                [
                    'date' => '2026-10-08',
                    'time' => '08:20:00',
                    'review_time' => '10:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Na-ilisdan na an 2.5-inch discharge gate valve san Samar Industrial. Na-pressure test, waray na tulo ngan hapsay na.',
                ],
                // Inspection 5: Oct 14, 2026 (Wednesday) - All OK
                [
                    'date' => '2026-10-14',
                    'time' => '08:10:00',
                    'review_time' => '10:00:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Tanan nga 14 items Passed. Puno an 10,000-liter water tank, andam mag-suporta kon may sunog.',
                ],
            ];

            foreach ($inspections as $ins) {
                $inspId = DB::table('inspections')->insertGetId([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $vTanker->id,
                    'driver_id' => $reposo->id,
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
            // 3. DAMAGE REPORT (Submitted by John Erol Reposo on Oct 02, 2026)
            // Grounding: Blown discharge valve seal causing severe pressure loss
            // -------------------------------------------------------------
            $this->info("3. Seeding 1 severe damage report for {$vTanker->plate_number} (Oct 02, 2026)...");

            DB::table('damage_reports')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $vTanker->id,
                'driver_id' => $reposo->id,
                'nature_of_damage' => 'Diri ma-hold an water pressure kay guba an discharge valve seal sa luyo, nag-aawas an tubig pag ginbobomba.',
                'suspected_parts' => '2.5-inch bronze discharge gate valve / neoprene flange gasket seal',
                'photo_path' => null,
                'date_reported' => '2026-10-02',
                'status' => 'Reviewed',
                'reviewed_by' => $admin->id,
                'reviewed_at' => '2026-10-02 10:00:00',
                'created_at' => '2026-10-02 09:30:00',
                'updated_at' => '2026-10-02 10:00:00',
            ]);

            // -------------------------------------------------------------
            // 4. REPAIR LOG (Completed on Oct 06, 2026 at Samar Industrial)
            // Restores EFG-4532 to Operational status (Rule 9)
            // -------------------------------------------------------------
            $this->info("4. Seeding 1 repair log for {$vTanker->plate_number} (Oct 06, 2026)...");

            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $vTanker->id,
                'driver_id' => $reposo->id,
                'repair_date' => '2026-10-06',
                'scope_of_work' => 'Replacement of 2.5-inch bronze discharge gate valve, flange re-facing, high-pressure silicone gasket installation, and hydro-static pressure testing.',
                'parts_replaced' => '2.5-inch bronze discharge gate valve, flange gasket kit, high-tensile mounting bolts',
                'repair_source' => RepairLog::SOURCE_EXTERNAL,
                'external_shop_name' => 'Samar Industrial & Hydraulic Supply',
                'cost' => 6850.00,
                'receipt_path' => null,
                'remarks' => 'Hydraulic gate valve successfully replaced and leak-tested up to 250 PSI. Water delivery flow certified 100% operational.',
                'created_at' => '2026-10-06 10:00:00',
                'updated_at' => '2026-10-06 14:00:00',
            ]);

            // -------------------------------------------------------------
            // 5. NOTIFICATIONS (Status Transitions & Alerts)
            // -------------------------------------------------------------
            $this->info('5. Seeding notifications for John Erol Reposo & Admin...');

            // Oct 02: Admin Flagged Inspection Alert
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_INSPECTION_FLAGGED,
                'title' => 'Inspection Reported an Issue',
                'message' => "{$vTanker->plate_number} — 1 item flagged: Hydraulic System (discharge valve leak noted by Reposo).",
                'data' => json_encode(['vehicle_id' => $vTanker->id, 'plate_number' => $vTanker->plate_number, 'driver' => 'John Erol Reposo']),
                'is_read' => 1,
                'read_at' => '2026-10-02 10:00:00',
                'created_at' => '2026-10-02 08:45:00',
                'updated_at' => '2026-10-02 10:00:00',
            ]);

            // Oct 02: Admin Damage Report Alert
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                'title' => 'New Damage Report Submitted',
                'message' => "John Erol Reposo reported discharge valve pressure loss on {$vTanker->plate_number}.",
                'data' => json_encode(['vehicle_id' => $vTanker->id, 'plate_number' => $vTanker->plate_number, 'driver' => 'John Erol Reposo']),
                'is_read' => 1,
                'read_at' => '2026-10-02 10:00:00',
                'created_at' => '2026-10-02 09:30:00',
                'updated_at' => '2026-10-02 10:00:00',
            ]);

            // Oct 02: Driver Reposo Status Update -> Not Operational
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $reposo->id,
                'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                'title' => 'Vehicle Status Updated',
                'message' => "{$vTanker->plate_number} is now Not Operational.",
                'data' => json_encode([
                    'plate' => $vTanker->plate_number,
                    'vehicle_id' => $vTanker->id,
                    'from_status' => Vehicle::STATUS_OPERATIONAL,
                    'to_status' => Vehicle::STATUS_NOT_OPERATIONAL,
                    'source' => VehicleStatusWriter::SOURCE_DAMAGE,
                    'remarks' => 'Grounded due to severe discharge valve seal leak causing water pressure loss.',
                ]),
                'is_read' => 1,
                'read_at' => '2026-10-02 10:15:00',
                'created_at' => '2026-10-02 10:00:00',
                'updated_at' => '2026-10-02 10:15:00',
            ]);

            // Oct 06: Driver Reposo Status Update -> Operational after Repair
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $reposo->id,
                'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                'title' => 'Vehicle Status Updated',
                'message' => "{$vTanker->plate_number} is now Operational.",
                'data' => json_encode([
                    'plate' => $vTanker->plate_number,
                    'vehicle_id' => $vTanker->id,
                    'from_status' => Vehicle::STATUS_NOT_OPERATIONAL,
                    'to_status' => Vehicle::STATUS_OPERATIONAL,
                    'source' => VehicleStatusWriter::SOURCE_REPAIR,
                    'remarks' => 'Hydraulic gate valve replaced and pressure-tested up to 250 PSI at Samar Industrial. Cleared for active duty.',
                ]),
                'is_read' => 1,
                'read_at' => '2026-10-06 14:30:00',
                'created_at' => '2026-10-06 14:00:00',
                'updated_at' => '2026-10-06 14:30:00',
            ]);

            // Ensure EFG-4532 vehicle record is updated to Operational
            DB::table('vehicles')->where('id', $vTanker->id)->update([
                'current_mileage' => 81650,
                'status' => Vehicle::STATUS_OPERATIONAL,
                'updated_at' => '2026-10-14 08:10:00',
            ]);
        });

        $this->info('John Erol Reposo & EFG-4532 UAT seeding completed successfully.');
        return self::SUCCESS;
    }
}

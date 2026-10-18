<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\InspectionChecklistItem;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed authentic historical UAT records for Manuel Tan & his assigned vehicle (PNP).
 *
 * Rules:
 * - Direct DB::table inserts to preserve past timestamps (Rule 1).
 * - Date Range: Sep 30 to Oct 20, 2026 (weekdays only, 9am - 5pm) (Rule 2).
 * - Language: Authentic Calbayognon Waray-waray using "sa" and "san" (Rule 3).
 * - Odometer progression monotonic (Rule 4).
 * - PM schedule realism: 1 Completed in August, 1 Due Soon in October (Rule 6).
 * - Idempotency: Cleans up previous Manuel Tan logs (Rule 7).
 * - Strictly null dummy attachments (Rule 8).
 * - Retains Operational status (non-grounding damage) (Rule 9).
 * - Mobile alerts for driver & admin (Rule 10).
 * - Pre-operational created_at calibration to Sep 14, 2026 (Rule 11).
 */
class SeedPnpTanCommand extends Command
{
    protected $signature = 'rvms:seed-pnp-tan {--fresh : Wipe previously seeded PNP UAT data before seeding}';

    protected $description = 'Seed authentic historical UAT records for Manuel Tan & assigned patrol vehicle (PNP)';

    public function handle(): int
    {
        $this->info('Starting historical UAT seeding for Manuel Tan & assigned vehicle (PNP)...');

        $agency = Agency::where('code', 'PNP')->first();
        if (! $agency) {
            $this->error('PNP agency record not found!');
            return self::FAILURE;
        }

        $admin = User::where('email', 'pnp.admin@email.com')->first()
            ?? User::where('agency_id', $agency->id)->where('role', User::ROLE_ADMIN)->first();

        if (! $admin) {
            $this->info('PNP admin user not found; creating pnp.admin@email.com...');
            $admin = User::create([
                'agency_id' => $agency->id,
                'role' => User::ROLE_ADMIN,
                'name' => 'PNP Administrator',
                'email' => 'pnp.admin@email.com',
                'password' => 'password',
                'status' => User::STATUS_ACTIVE,
                'created_at' => '2026-09-14 08:00:00',
                'updated_at' => '2026-09-14 08:00:00',
            ]);
        }

        $tan = User::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('name', 'like', '%Manuel Tan%')
                  ->orWhere('email', 'like', '%tan%');
            })->first();

        if (! $tan) {
            $this->info('Driver Manuel Tan not found; creating user account...');
            $tan = User::create([
                'agency_id' => $agency->id,
                'role' => User::ROLE_DRIVER,
                'name' => 'Manuel Tan',
                'email' => 'manuel.tan@email.com',
                'password' => 'password',
                'status' => User::STATUS_ACTIVE,
                'license_number' => 'N02-16-405566',
                'license_expiry_date' => '2027-10-05',
                'created_at' => '2026-09-14 09:00:00',
                'updated_at' => '2026-09-14 09:00:00',
            ]);
        }

        $vAssigned = Vehicle::where('agency_id', $agency->id)
            ->where('assigned_driver_id', $tan->id)
            ->first()
            ?? Vehicle::where('agency_id', $agency->id)
                ->where(function ($q) {
                    $q->where('plate_number', 'PNP-1067')
                      ->orWhere('plate_number', 'PNP 1067');
                })->first();

        if (! $vAssigned) {
            $vAssigned = Vehicle::where('agency_id', $agency->id)->where('plate_number', 'PNP-1021')->first();
        }

        if (! $vAssigned) {
            $this->info('Assigned vehicle not found; creating PNP-1067 (Toyota Hilux Mobile Patrol)...');
            $vAssigned = Vehicle::create([
                'agency_id' => $agency->id,
                'plate_number' => 'PNP-1067',
                'type' => 'Mobile Patrol',
                'make' => 'Toyota',
                'model' => 'Hilux',
                'engine_number' => '2GD-FTV-330145',
                'chassis_number' => 'MR0HA8CD200148273',
                'current_mileage' => 54200,
                'status' => Vehicle::STATUS_OPERATIONAL,
                'assigned_driver_id' => $tan->id,
                'created_at' => '2026-09-14 08:30:00',
                'updated_at' => '2026-09-14 08:30:00',
            ]);
        } else {
            if ($vAssigned->assigned_driver_id !== $tan->id) {
                $vAssigned->update(['assigned_driver_id' => $tan->id]);
            }
        }

        $checklists = InspectionChecklistItem::forAgencyCode('PNP')->get();
        if ($checklists->isEmpty()) {
            $this->error('BLOWBAGETS checklist catalog is empty.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->info('Cleaning up all PNP operational history for fresh seeding...');
            $pnpInspIds = DB::table('inspections')->where('agency_id', $agency->id)->pluck('id');
            DB::table('inspection_items')->whereIn('inspection_id', $pnpInspIds)->delete();
            DB::table('inspections')->where('agency_id', $agency->id)->delete();
            DB::table('damage_reports')->where('agency_id', $agency->id)->delete();
            DB::table('repair_logs')->where('agency_id', $agency->id)->delete();
            DB::table('pm_schedules')->where('agency_id', $agency->id)->delete();
            DB::table('dispatches')->where('agency_id', $agency->id)->delete();
            DB::table('notifications')->where('agency_id', $agency->id)->delete();
        } else {
            $this->info('Cleaning up existing Tan-authored logs on assigned vehicle to ensure idempotency...');
            $tanInspIds = DB::table('inspections')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vAssigned->id)
                ->where('driver_id', $tan->id)
                ->pluck('id');

            DB::table('inspection_items')->whereIn('inspection_id', $tanInspIds)->delete();
            DB::table('inspections')->whereIn('id', $tanInspIds)->delete();

            DB::table('damage_reports')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vAssigned->id)
                ->where('driver_id', $tan->id)
                ->delete();

            DB::table('pm_schedules')
                ->where('agency_id', $agency->id)
                ->where('vehicle_id', $vAssigned->id)
                ->delete();

            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $tan->id)
                ->delete();

            DB::table('notifications')
                ->where('agency_id', $agency->id)
                ->where('user_id', $admin->id)
                ->where(function ($q) use ($vAssigned) {
                    $q->where('data', 'like', "%{$vAssigned->plate_number}%")
                      ->orWhere('message', 'like', "%{$vAssigned->plate_number}%")
                      ->orWhere('data', 'like', '%Manuel Tan%');
                })
                ->delete();
        }

        DB::transaction(function () use ($agency, $admin, $vAssigned, $tan, $checklists) {
            // -------------------------------------------------------------
            // 0. ACCOUNT & VEHICLE CREATION TIMESTAMPS (Rule 11)
            // Ensure accounts and vehicle exist prior to first operational submission (Sep 30, 2026)
            // -------------------------------------------------------------
            DB::table('users')->where('id', $admin->id)->update([
                'created_at' => '2026-09-14 08:00:00',
            ]);

            DB::table('users')->where('id', $tan->id)->update([
                'created_at' => '2026-09-14 09:00:00',
            ]);

            DB::table('vehicles')->where('id', $vAssigned->id)->update([
                'created_at' => '2026-09-14 08:30:00',
            ]);

            // -------------------------------------------------------------
            // 1. PREVENTIVE MAINTENANCE SCHEDULES (1 Completed, 1 Due Soon)
            // -------------------------------------------------------------
            $this->info("1. Seeding 2 PM schedules for {$vAssigned->plate_number} (1 Completed, 1 Due Soon)...");

            // PM 1: Completed on August 14, 2026 (50,000 km PMS at GSO)
            DB::table('pm_schedules')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $vAssigned->id,
                'service_target' => '50,000 km Major PMS - Engine Oil, Fuel Filter, Differential Gear Oil & Brake Pad Service',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 45000,
                'due_mileage' => 50000,
                'due_date' => null,
                'due_soon_threshold_km' => 1000,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_COMPLETED,
                'date_serviced' => '2026-08-14',
                'completion_mileage' => 49980,
                'completion_repair_source' => RepairLog::SOURCE_GSO,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => 'Fully synthetic diesel oil 15W-40 (7.5L), OEM oil filter cartridge, primary fuel filter, differential oil (3L)',
                'completion_remarks' => 'Completed 50,000 km PMS at GSO Motorpool under Job Order #PNP-2026-08-019. Patrol vehicle tested roadworthy.',
                'created_at' => '2026-08-01 09:00:00',
                'updated_at' => '2026-08-14 15:30:00',
            ]);

            // PM 2: Active Due Soon (55,000 km PMS)
            $pmDueSoonId = DB::table('pm_schedules')->insertGetId([
                'agency_id' => $agency->id,
                'vehicle_id' => $vAssigned->id,
                'service_target' => '55,000 km Periodic Maintenance Service (PMS) - Brake Pads, AC Cabin Filter & Suspension Inspection',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 49980,
                'due_mileage' => 55000,
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
                'created_at' => '2026-09-14 09:30:00',
                'updated_at' => '2026-10-05 08:30:00',
            ]);

            // -------------------------------------------------------------
            // 2. DAILY BLOWBAGETS INSPECTIONS (Sep 30 - Oct 20, 2026)
            // Authored by Manuel Tan using Calbayognon Waray "sa" and "san"
            // -------------------------------------------------------------
            $this->info("2. Seeding 5 daily BLOWBAGETS inspections for {$vAssigned->plate_number}...");

            $inspections = [
                // Inspection 1: Sep 30, 2026 (Wednesday) - All OK
                [
                    'date' => '2026-09-30',
                    'time' => '09:15:00',
                    'review_time' => '11:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Ginsusi an lana san makina, tubig sa radiator, ngan battery terminals. Andam an patrol car para sa routine roving checkpoint.',
                ],
                // Inspection 2: Oct 05, 2026 (Monday) - All OK
                [
                    'date' => '2026-10-05',
                    'time' => '09:20:00',
                    'review_time' => '11:30:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Gumagana an rooftop emergency lightbar, siren, ngan mobile radio. Hapsay an dalagan san makina ngan preno.',
                ],
                // Inspection 3: Oct 08, 2026 (Thursday) - Flagged Tires
                [
                    'date' => '2026-10-08',
                    'time' => '09:10:00',
                    'review_time' => '11:00:00',
                    'flagged_item' => 'Tires',
                    'flagged_note' => 'Tires: Medyo ubos an hangin sa ligid sa unahan sa wala (front left tire mga 28 psi) tikang san patrolya sa karsada sa Brgy. Dagum. Gin-pahanginan ko sa Petron 35 psi.',
                    'remarks' => 'Low tire pressure on front left tire, re-inflated to 35 psi sa Petron station. Tread depth and other tires in good condition.',
                ],
                // Inspection 4: Oct 14, 2026 (Wednesday) - All OK
                [
                    'date' => '2026-10-14',
                    'time' => '09:25:00',
                    'review_time' => '10:45:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Kompleto an emergency tools, hydraulic jack, ngan spare tire sa likod. Tanan nga fluid levels normal.',
                ],
                // Inspection 5: Oct 20, 2026 (Tuesday) - Flagged Directional Signals
                [
                    'date' => '2026-10-20',
                    'time' => '09:15:00',
                    'review_time' => '11:20:00',
                    'flagged_item' => 'Directional Signals',
                    'flagged_note' => 'Directional Signals: Medyo malubay an pagsiga san signal light sa unahan sa tuo. Ginsusi an wiring socket, nag-aungot gamay san karsada.',
                    'remarks' => 'Front right signal light bulb socket re-seated and cleaned terminal contact. Flasher pulsing normally.',
                ],
            ];

            foreach ($inspections as $ins) {
                $inspId = DB::table('inspections')->insertGetId([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $vAssigned->id,
                    'driver_id' => $tan->id,
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
            // 3. DAMAGE REPORT (Submitted by Manuel Tan on Oct 12, 2026)
            // Incident: Auxiliary front spotlight mounting bracket loose during checkpoint
            // -------------------------------------------------------------
            $this->info("3. Seeding 1 damage report for {$vAssigned->plate_number} (Oct 12, 2026)...");

            DB::table('damage_reports')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $vAssigned->id,
                'driver_id' => $tan->id,
                'nature_of_damage' => 'Medyo nabaliko an bracket san auxiliary front bullbar spotlight samtang nag-aayos san checkpoint barrier sa karsada sa Brgy. San Policarpo. Nagkikirot an suga kon nalulubak an dalan.',
                'suspected_parts' => 'Auxiliary LED spotlight mounting bracket / ground wire terminal',
                'photo_path' => null,
                'date_reported' => '2026-10-12',
                'status' => 'Reviewed',
                'reviewed_by' => $admin->id,
                'reviewed_at' => '2026-10-12 14:30:00',
                'created_at' => '2026-10-12 10:15:00',
                'updated_at' => '2026-10-12 14:30:00',
            ]);

            // -------------------------------------------------------------
            // 4. NOTIFICATIONS (PM Reminder & Damage Alert)
            // -------------------------------------------------------------
            $this->info('4. Seeding notifications for Manuel Tan & Admin...');

            // Oct 05: Driver Tan PM Due Soon Alert
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $tan->id,
                'type' => Notification::TYPE_PM_REMINDER,
                'title' => 'Preventive Maintenance',
                'message' => "{$vAssigned->plate_number} — 55,000 km Periodic Maintenance Service (PMS) is due soon.",
                'data' => json_encode([
                    'pm_schedule_id' => $pmDueSoonId,
                    'vehicle_id' => $vAssigned->id,
                    'plate' => $vAssigned->plate_number,
                    'service_target' => '55,000 km Periodic Maintenance Service (PMS) - Brake Pads, AC Cabin Filter & Suspension Inspection',
                    'state' => 'Due Soon',
                ]),
                'is_read' => 0,
                'read_at' => null,
                'created_at' => '2026-10-05 08:30:00',
                'updated_at' => '2026-10-05 08:30:00',
            ]);

            // Oct 05: Admin PM Due Soon Alert
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_PM_DUE_SOON,
                'title' => 'PM Due Soon',
                'message' => "{$vAssigned->plate_number} reached 54,200 km Due Soon threshold for 55,000 km Periodic Maintenance Service (PMS).",
                'data' => json_encode([
                    'pm_schedule_id' => $pmDueSoonId,
                    'vehicle_id' => $vAssigned->id,
                    'plate' => $vAssigned->plate_number,
                    'service_target' => '55,000 km Periodic Maintenance Service (PMS) - Brake Pads, AC Cabin Filter & Suspension Inspection',
                    'state' => PmSchedule::STATUS_DUE_SOON,
                ]),
                'is_read' => 0,
                'read_at' => null,
                'created_at' => '2026-10-05 08:30:00',
                'updated_at' => '2026-10-05 08:30:00',
            ]);

            // Oct 08: Admin Flagged Inspection Alert (Tires)
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_INSPECTION_FLAGGED,
                'title' => 'Inspection Reported an Issue',
                'message' => "{$vAssigned->plate_number} — 1 item flagged: Tires (low front left tire pressure resolved sa Petron).",
                'data' => json_encode(['vehicle_id' => $vAssigned->id, 'plate_number' => $vAssigned->plate_number, 'driver' => 'Manuel Tan']),
                'created_at' => '2026-10-08 09:10:00',
                'updated_at' => '2026-10-08 09:10:00',
            ]);

            // Oct 12: Admin Damage Report Alert
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                'title' => 'New Damage Report Submitted',
                'message' => "Manuel Tan reported loose auxiliary spotlight bracket on {$vAssigned->plate_number}.",
                'data' => json_encode(['vehicle_id' => $vAssigned->id, 'plate_number' => $vAssigned->plate_number, 'driver' => 'Manuel Tan']),
                'created_at' => '2026-10-12 10:15:00',
                'updated_at' => '2026-10-12 10:15:00',
            ]);

            // Oct 20: Admin Flagged Inspection Alert (Directional Signals)
            DB::table('notifications')->insert([
                'agency_id' => $agency->id,
                'user_id' => $admin->id,
                'type' => Notification::TYPE_INSPECTION_FLAGGED,
                'title' => 'Inspection Reported an Issue',
                'message' => "{$vAssigned->plate_number} — 1 item flagged: Directional Signals (front right signal socket re-seated sa terminal).",
                'data' => json_encode(['vehicle_id' => $vAssigned->id, 'plate_number' => $vAssigned->plate_number, 'driver' => 'Manuel Tan']),
                'created_at' => '2026-10-20 09:15:00',
                'updated_at' => '2026-10-20 09:15:00',
            ]);
        });

        $this->info('Successfully seeded historical UAT records for Manuel Tan & assigned patrol vehicle!');
        $this->table(
            ['Entity', 'Count for Manuel Tan'],
            [
                ['Inspections', DB::table('inspections')->where('driver_id', $tan->id)->count()],
                ['Damage Reports', DB::table('damage_reports')->where('driver_id', $tan->id)->count()],
                ['PM Schedules', DB::table('pm_schedules')->where('vehicle_id', $vAssigned->id)->count()],
                ['Notifications (Tan)', DB::table('notifications')->where('user_id', $tan->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

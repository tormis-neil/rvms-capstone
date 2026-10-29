<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\InspectionChecklistItem;
use App\Models\Notification;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed authentic, realistic historical UAT data specifically for Rowel Gamao (Primary Driver) & SHA 2625 (CHO).
 *
 * Rules:
 * - Direct DB::table inserts to strictly preserve past created_at timestamps.
 * - Dates: September 15 through October 09, 2026 (weekdays only, 9am - 5pm).
 * - Language: Simple English + authentic Calbayognon Waray-waray (strictly using "sa" instead of "ha").
 * - Keeps shared SHA 2625 dispatches, repair logs, PM schedule, and vehicle status intact.
 * - Appends Gamao's inspections, damage report, and notifications alongside Balloca's data.
 */
class SeedChoGamaoCommand extends Command
{
    protected $signature = 'rvms:seed-cho-gamao';

    protected $description = 'Seed authentic historical UAT records specifically for Rowel Gamao (Primary Driver) & SHA 2625';

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

        $gamao = User::where('email', 'gamao.cho@email.com')->first();
        if (! $gamao) {
            $this->error('Driver Gamao (gamao.cho@email.com) not found in database.');
            return self::FAILURE;
        }

        $checklists = InspectionChecklistItem::forAgencyCode('CHO')->get();
        if ($checklists->isEmpty()) {
            $this->error('BLOWBAGETS checklist catalog is empty.');
            return self::FAILURE;
        }

        // Clean up any existing inspections, damage reports, and notifications for Gamao to ensure idempotency
        $this->info('Cleaning up any existing Gamao-authored operational logs to ensure idempotency...');
        
        $gamaoInspIds = DB::table('inspections')
            ->where('agency_id', $agency->id)
            ->where('driver_id', $gamao->id)
            ->pluck('id');
            
        DB::table('inspection_items')->whereIn('inspection_id', $gamaoInspIds)->delete();
        DB::table('inspections')->whereIn('id', $gamaoInspIds)->delete();

        DB::table('damage_reports')
            ->where('agency_id', $agency->id)
            ->where('driver_id', $gamao->id)
            ->delete();

        // Delete previous admin notifications that were for Gamao submissions
        DB::table('notifications')
            ->where('agency_id', $agency->id)
            ->where('user_id', $admin->id)
            ->where('data', 'like', '%Rowel Gamao%')
            ->delete();

        // Delete Gamao driver notifications (except status update which we'll ensure)
        DB::table('notifications')
            ->where('agency_id', $agency->id)
            ->where('user_id', $gamao->id)
            ->delete();

        $pm = DB::table('pm_schedules')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v2625->id)
            ->where('service_target', 'like', '%60,000 km%')
            ->first();
        $pmId = $pm?->id;

        DB::transaction(function () use ($agency, $admin, $v2625, $gamao, $checklists, $pmId) {
            // -------------------------------------------------------------
            // 0. ACCOUNT & VEHICLE CREATION TIMESTAMPS
            // Ensure accounts and vehicle exist prior to first operational submission (Sep 15, 2026)
            // -------------------------------------------------------------
            DB::table('users')->where('id', $admin->id)->update([
                'created_at' => '2026-09-14 08:00:00',
            ]);

            DB::table('users')->where('id', $gamao->id)->update([
                'created_at' => '2026-09-14 09:30:00',
            ]);

            DB::table('vehicles')->where('id', $v2625->id)->update([
                'created_at' => '2026-09-14 08:30:00',
            ]);

            // -------------------------------------------------------------
            // 1. DAILY BLOWBAGETS INSPECTIONS (Submitted by Rowel Gamao)
            // Alternating shift dates with Balloca: Sep 15, Sep 21, Sep 28, Oct 05, Oct 09
            // Language: Waray-waray using "sa" (not "ha")
            // -------------------------------------------------------------
            $this->info('1. Seeding 5 daily inspections authored by Rowel Gamao...');

            $inspections = [
                [
                    'date' => '2026-09-15',
                    'time' => '09:20:00',
                    'review_time' => '11:30:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Kompleto an lana san makina ngan tubig sa radiator. Waray tulo sa idarom. Hapsay an dalagan.',
                ],
                [
                    'date' => '2026-09-21',
                    'time' => '09:15:00',
                    'review_time' => '10:45:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Ginsusi an preno ngan mga suga sa gawas. Tanan nga dashboard gauge normal an basa. Andam an Hilux para sa biyahe.',
                ],
                [
                    'date' => '2026-09-28',
                    'time' => '09:30:00',
                    'review_time' => '11:45:00',
                    'flagged_item' => 'Tires',
                    'flagged_note' => 'Tires: Guti-ay an hangin sa tuo nga ligid sa luyo (rear right tire). Gin-pahanginan ko sa Petron 34 psi.',
                    'remarks' => 'Low pressure on rear right tire, inflated to 34 psi sa Petron. All other tires and spare in good condition.',
                ],
                [
                    'date' => '2026-10-05',
                    'time' => '09:10:00',
                    'review_time' => '11:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Naka-set-up an jack, yawi sa ligid, ngan early warning device sa likod san lingkoran. Maupay an dalagan san makina.',
                ],
                [
                    'date' => '2026-10-09',
                    'time' => '09:25:00',
                    'review_time' => '11:20:00',
                    'flagged_item' => 'Directional Signals',
                    'flagged_note' => 'Directional Signals: Medyo malubay an blinking san signal light sa unahan sa tuo. Ginsusi an bombilya, nagkikirot-kirot kon nalulubak sa karsada.',
                    'remarks' => 'Front right directional signal bulb socket slightly loose, re-seated tightly sa terminal. Functioning normally.',
                ],
            ];

            foreach ($inspections as $ins) {
                $inspId = DB::table('inspections')->insertGetId([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v2625->id,
                    'driver_id' => $gamao->id,
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
            // 2. DAMAGE REPORT (Submitted by Rowel Gamao on Sep 22, 2026)
            // Incident: Tailgate latch loose during travel to Brgy. Hamorawon
            // Reviewed on Sep 23, 2026 by Admin
            // Language: Waray-waray using "sa" (not "ha")
            // -------------------------------------------------------------
            $this->info('2. Seeding Damage Report submitted by Rowel Gamao...');

            DB::table('damage_reports')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2625->id,
                'driver_id' => $gamao->id,
                'nature_of_damage' => 'Nalugak an trangka san tailgate latch sa likod san cargo bed samtang nagbabagtas sa karsada pakadto sa Brgy. Hamorawon. Nagraragubrob kon naigo sa libaong.',
                'suspected_parts' => 'Tailgate lock striker plate / rubber bumper cushion',
                'photo_path' => null,
                'date_reported' => '2026-09-22',
                'status' => 'Reviewed',
                'reviewed_by' => $admin->id,
                'reviewed_at' => '2026-09-23 09:30:00',
                'created_at' => '2026-09-22 10:15:00',
                'updated_at' => '2026-09-23 09:30:00',
            ]);

            // -------------------------------------------------------------
            // 3. NOTIFICATIONS (Direct DB Inserts with Exact Past Timestamps)
            // -------------------------------------------------------------
            $this->info('3. Seeding Chronological Notifications for Rowel Gamao & Admin...');

            $notifs = [
                // Sep 17, 2026 — Driver Gamao: Dispatched (Trip 1 departure)
                [
                    'user_id' => $gamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Administrative Travel — Brgy. San Policarpo & Brgy. Trinidad Health Stations.',
                    ]),
                    'created_at' => '2026-09-17 08:30:00',
                ],
                // Sep 17, 2026 — Driver Gamao: Returned from Trip 1
                [
                    'user_id' => $gamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Delivered cold-chain vaccine carriers and routine immunization supplies to barangay health stations.',
                    ]),
                    'created_at' => '2026-09-17 12:00:00',
                ],
                // Sep 22, 2026 10:15 AM — Admin: New Damage Report Submitted by Gamao
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                    'title' => 'New Damage Report Submitted',
                    'message' => 'Rowel Gamao reported loose tailgate latch mechanism on SHA 2625.',
                    'data' => json_encode(['vehicle_id' => $v2625->id, 'plate_number' => 'SHA 2625', 'driver' => 'Rowel Gamao']),
                    'created_at' => '2026-09-22 10:15:00',
                ],
                // Sep 24, 2026 — Driver Gamao: Dispatched (Trip 2 departure)
                [
                    'user_id' => $gamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Administrative Travel — Brgy. Dagum & Brgy. Matobato Health Centers.',
                    ]),
                    'created_at' => '2026-09-24 13:00:00',
                ],
                // Sep 24, 2026 — Driver Gamao: Returned from Trip 2
                [
                    'user_id' => $gamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Transported Dengue vector control misting equipment and larvicide chemicals.',
                    ]),
                    'created_at' => '2026-09-24 16:00:00',
                ],
                // Sep 28, 2026 09:30 AM — Admin: Flagged inspection on SHA 2625 (Tires)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'SHA 2625 — 1 item flagged: Tires (low rear right tire pressure resolved sa Petron).',
                    'data' => json_encode(['vehicle_id' => $v2625->id, 'plate_number' => 'SHA 2625', 'driver' => 'Rowel Gamao']),
                    'created_at' => '2026-09-28 09:30:00',
                ],
                // Oct 02, 2026 — Driver Gamao: Dispatched (Trip 3 departure)
                [
                    'user_id' => $gamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Administrative Travel — City Hall Motorpool & GSO Supply Office.',
                    ]),
                    'created_at' => '2026-10-02 10:00:00',
                ],
                // Oct 02, 2026 — Driver Gamao: Returned from Trip 3
                [
                    'user_id' => $gamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Hauled emergency medical supply boxes and health record logbooks from City Hall.',
                    ]),
                    'created_at' => '2026-10-02 11:45:00',
                ],
                // Oct 05, 2026 08:30 AM — Driver Gamao: PM Reminder Due Soon (shared parity with Balloca)
                [
                    'user_id' => $gamao->id,
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
                // Oct 07, 2026 — Driver Gamao: Dispatched (Trip 4 departure)
                [
                    'user_id' => $gamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Dispatched.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_OPERATIONAL,
                        'to_status' => Vehicle::STATUS_DISPATCHED,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
                        'remarks' => 'Dispatched for Administrative Travel — Brgy. Obrero Rural Health Unit.',
                    ]),
                    'created_at' => '2026-10-07 09:30:00',
                ],
                // Oct 07, 2026 — Driver Gamao: Returned from Trip 4
                [
                    'user_id' => $gamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 is now Operational.',
                    'data' => json_encode([
                        'plate' => 'SHA 2625',
                        'vehicle_id' => $v2625->id,
                        'from_status' => Vehicle::STATUS_DISPATCHED,
                        'to_status' => Vehicle::STATUS_OPERATIONAL,
                        'source' => VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                        'remarks' => 'Delivered rural health maternal kits and nutrition supplies to upland health center.',
                    ]),
                    'created_at' => '2026-10-07 12:30:00',
                ],
                // Oct 09, 2026 09:25 AM — Admin: Flagged inspection on SHA 2625 (Directional Signals)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'SHA 2625 — 1 item flagged: Directional Signals (front right signal socket re-seated sa terminal).',
                    'data' => json_encode(['vehicle_id' => $v2625->id, 'plate_number' => 'SHA 2625', 'driver' => 'Rowel Gamao']),
                    'created_at' => '2026-10-09 09:25:00',
                ],
                // Oct 13, 2026 09:00 AM — Driver Gamao: Status update to Not Operational
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
                        'remarks' => 'Grounded for clutch disc, pressure plate, and release bearing overhaul due to severe clutch slipping on steep inclines.',
                    ]),
                    'created_at' => '2026-10-13 09:00:00',
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

        $this->info('Successfully seeded authentic historical UAT data for Rowel Gamao (Primary Driver) & SHA 2625!');
        $this->table(
            ['Entity', 'Count for Gamao / Total for SHA 2625'],
            [
                ['Inspections (Gamao)', DB::table('inspections')->where('driver_id', $gamao->id)->count()],
                ['Inspections (Total SHA 2625)', DB::table('inspections')->where('vehicle_id', $v2625->id)->count()],
                ['Damage Reports (Gamao)', DB::table('damage_reports')->where('driver_id', $gamao->id)->count()],
                ['Damage Reports (Total SHA 2625)', DB::table('damage_reports')->where('vehicle_id', $v2625->id)->count()],
                ['Gamao Notifications', DB::table('notifications')->where('user_id', $gamao->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

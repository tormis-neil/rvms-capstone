<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\InspectionChecklistItem;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed authentic historical UAT records specifically for Reynaldo Serrato & SHA 1247 (CHO Ambulance).
 *
 * Rules:
 * - Direct DB::table inserts to strictly preserve past created_at timestamps.
 * - Date Range: Sept 28 to Oct 22, 2026 (weekdays only, 9am - 5pm).
 * - Language: Simple English + authentic Calbayognon Waray (strictly using "san" and "sa", replacing all "han" and "ha").
 * - Preserves all users, vehicles, and existing records for SHA 2625 (Balloca & Gamao).
 */
class SeedChoSerratoCommand extends Command
{
    protected $signature = 'rvms:seed-cho-serrato';

    protected $description = 'Seed authentic historical UAT records specifically for Reynaldo Serrato & SHA 1247 (Ambulance)';

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

        $v1247 = Vehicle::where('agency_id', $agency->id)->where('plate_number', 'SHA 1247')->first();
        if (! $v1247) {
            $this->error('Vehicle SHA 1247 not found in database.');
            return self::FAILURE;
        }

        $serrato = User::where('email', 'serrato.cho@email.com')->first();
        if (! $serrato) {
            $this->error('Driver Reynaldo Serrato (serrato.cho@email.com) not found in database.');
            return self::FAILURE;
        }

        $checklists = InspectionChecklistItem::forAgencyCode('CHO')->get();
        if ($checklists->isEmpty()) {
            $this->error('BLOWBAGETS checklist catalog is empty.');
            return self::FAILURE;
        }

        $this->info('Cleaning up existing SHA 1247 operational logs to ensure idempotency...');

        // Clear previous inspections & items for SHA 1247
        $inspIds = DB::table('inspections')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1247->id)
            ->pluck('id');

        DB::table('inspection_items')->whereIn('inspection_id', $inspIds)->delete();
        DB::table('inspections')->whereIn('id', $inspIds)->delete();

        // Clear previous damage reports for SHA 1247
        DB::table('damage_reports')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1247->id)
            ->delete();

        // Clear previous repair logs for SHA 1247
        DB::table('repair_logs')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1247->id)
            ->delete();

        // Clear previous PM schedules for SHA 1247
        DB::table('pm_schedules')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1247->id)
            ->delete();

        // Clear previous dispatches for SHA 1247
        DB::table('dispatches')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v1247->id)
            ->delete();

        // Clear previous notifications for Serrato and admin alerts for SHA 1247 / Serrato
        DB::table('notifications')
            ->where('agency_id', $agency->id)
            ->where('user_id', $serrato->id)
            ->delete();

        DB::table('notifications')
            ->where('agency_id', $agency->id)
            ->where('user_id', $admin->id)
            ->where(function ($q) use ($v1247) {
                $q->where('data', 'like', '%SHA 1247%')
                  ->orWhere('data', 'like', '%Reynaldo Serrato%');
            })
            ->delete();

        DB::transaction(function () use ($agency, $admin, $v1247, $serrato, $checklists) {
            // -------------------------------------------------------------
            // 1. REPAIR LOGS (3 Distinct Historical Repairs: Jan to Aug 2026)
            // -------------------------------------------------------------
            $this->info('1. Seeding 3 historical repair logs for SHA 1247 (Jan - Aug 2026)...');

            // Repair 1: Feb 12, 2026 — GSO Motorpool (Brake pads & rotors)
            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1247->id,
                'driver_id' => $serrato->id,
                'repair_date' => '2026-02-12',
                'scope_of_work' => 'Replaced front brake pads and resurfaced front brake rotors after high-speed emergency response pulsation.',
                'parts_replaced' => 'Front ceramic brake pad kit (OEM Toyota), 1L DOT 4 synthetic brake fluid',
                'cost' => 3450.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Completed under Job Order #CHO-2026-02-018. Front rotor runout corrected. Emergency braking performance road tested safe.',
                'created_at' => '2026-02-12 14:00:00',
                'updated_at' => '2026-02-12 14:00:00',
            ]);

            // Repair 2: May 08, 2026 — External Shop (Siren driver & lightbar relays)
            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1247->id,
                'driver_id' => $serrato->id,
                'repair_date' => '2026-05-08',
                'scope_of_work' => 'Replaced failing auxiliary siren horn driver unit and rewiring of dual roof LED lightbar strobe controller relay.',
                'parts_replaced' => 'Federal Signal 100W Siren Speaker Driver & 40A Heavy-Duty 12V Automotive Relays (2 pcs)',
                'cost' => 5800.00,
                'repair_source' => RepairLog::SOURCE_EXTERNAL,
                'external_shop_name' => 'Calbayog Auto Electrical & Battery Center',
                'receipt_path' => 'receipts/cho_ambulance_siren_or_20260508.pdf',
                'remarks' => 'Audible emergency warning siren restored to full 100W decibel level for clearing dense city traffic. Official receipt filed.',
                'created_at' => '2026-05-08 15:30:00',
                'updated_at' => '2026-05-08 15:30:00',
            ]);

            // Repair 3: Aug 19, 2026 — GSO Motorpool (Power steering return hose)
            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1247->id,
                'driver_id' => $serrato->id,
                'repair_date' => '2026-08-19',
                'scope_of_work' => 'Replaced leaking high-pressure power steering return hose line and flushed power steering hydraulic fluid.',
                'parts_replaced' => 'Power steering pressure return hose line, ATF Dexron III steering fluid (2L)',
                'cost' => 2650.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Power steering fluid leak rectified under GSO Job Order #CHO-2026-08-041. Steering wheel response smooth and leak-free.',
                'created_at' => '2026-08-19 13:30:00',
                'updated_at' => '2026-08-19 13:30:00',
            ]);

            // -------------------------------------------------------------
            // 2. PREVENTIVE MAINTENANCE SCHEDULE (Time-Based PM for SHA 1247)
            // Specifies parts in service_target and Due Soon threshold
            // -------------------------------------------------------------
            $this->info('2. Seeding 1 Time-Based PM schedule for SHA 1247 (Due Soon status)...');

            DB::table('pm_schedules')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1247->id,
                'service_target' => 'Semi-Annual Ambulance PMS (Brake Pads, Suction Pump Filter, AC Cabin Filter & Alternator Belt)',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_TIME,
                'interval_km' => null,
                'last_pm_mileage' => null,
                'due_mileage' => null,
                'due_date' => '2026-10-31',
                'due_soon_threshold_km' => null,
                'due_soon_threshold_days' => 21,
                'status' => PmSchedule::STATUS_DUE_SOON,
                'date_serviced' => null,
                'completion_mileage' => null,
                'completion_repair_source' => null,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => null,
                'completion_remarks' => null,
                'created_at' => '2026-09-28 09:30:00',
                'updated_at' => '2026-10-10 08:30:00',
            ]);

            // -------------------------------------------------------------
            // 3. DAILY BLOWBAGETS INSPECTIONS (6 Inspections by Reynaldo Serrato)
            // Date Range: Sept 28 - Oct 22, 2026 (weekdays only, 9am - 5pm)
            // Language: Strict Waray using "sa" and "san" (no "ha" or "han")
            // -------------------------------------------------------------
            $this->info('3. Seeding 6 daily inspections authored by Reynaldo Serrato...');

            $inspections = [
                [
                    'date' => '2026-09-29',
                    'time' => '09:15:00',
                    'review_time' => '11:15:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Siren, emergency flasher, ngan oxygen tank regulator gumagana hin maopay. Hapsay an dalagan san makina.',
                ],
                [
                    'date' => '2026-10-02',
                    'time' => '09:20:00',
                    'review_time' => '11:30:00',
                    'flagged_item' => 'Water',
                    'flagged_note' => 'Water: Guti-ay an sulod san coolant reserve tank sa radiator. Gin-dugangan ko hin distilled water sa CHO motorpool.',
                    'remarks' => 'Coolant reserve level replenished with distilled water sa motorpool. Temperature gauge steady.',
                ],
                [
                    'date' => '2026-10-06',
                    'time' => '09:10:00',
                    'review_time' => '10:45:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Ginsusi an emergency lights ngan siren amplifier. Kompleto an first-aid stretcher locks sa likod.',
                ],
                [
                    'date' => '2026-10-12',
                    'time' => '09:25:00',
                    'review_time' => '11:40:00',
                    'flagged_item' => 'Tires',
                    'flagged_note' => 'Tires: Maluya an hangin sa wala nga ligid sa unahan (front left tire: 28 psi). Gin-pahanginan ko sa Shell pakadto 35 psi.',
                    'remarks' => 'Low pressure on front left tire, inflated to 35 psi sa Shell station. Tread depth still good.',
                ],
                [
                    'date' => '2026-10-16',
                    'time' => '09:15:00',
                    'review_time' => '11:00:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Maupay an apak san preno ngan gumagana an suction machine sa sulod san ambulansya. Andam sa emergency response.',
                ],
                [
                    'date' => '2026-10-21',
                    'time' => '09:30:00',
                    'review_time' => '11:30:00',
                    'flagged_item' => null,
                    'flagged_note' => null,
                    'remarks' => 'Battery voltage normal sa 12.6V. Tanan nga cabin lights ngan warning beacon andam para sa biyahe.',
                ],
            ];

            foreach ($inspections as $ins) {
                $inspId = DB::table('inspections')->insertGetId([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v1247->id,
                    'driver_id' => $serrato->id,
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
            // 4. DAMAGE REPORT (1 Incident on Oct 08, 2026)
            // Language: Strict Waray using "sa" and "san" (no "ha" or "han")
            // -------------------------------------------------------------
            $this->info('4. Seeding Damage Report submitted by Reynaldo Serrato on Oct 08, 2026...');

            DB::table('damage_reports')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1247->id,
                'driver_id' => $serrato->id,
                'nature_of_damage' => 'Medyo nag-uungot an sliding door latch lock sa tuo nga pultahan san ambulance cabin pag-abre samtang nagbababa hin pasyente sa Brgy. Rawis. Makuri ig-sirado hin maopay kon diri iduso hin makusog.',
                'suspected_parts' => 'Ambulance cabin sliding door lower guide roller / center hinge latch mechanism',
                'photo_path' => null,
                'date_reported' => '2026-10-08',
                'status' => 'Reviewed',
                'reviewed_by' => $admin->id,
                'reviewed_at' => '2026-10-09 09:15:00',
                'created_at' => '2026-10-08 10:30:00',
                'updated_at' => '2026-10-09 09:15:00',
            ]);

            // -------------------------------------------------------------
            // 5. DISPATCHES (3 Dispatches: 2 Patient Transports under Others + 1 Admin Travel)
            // Progressing from 39,520 km -> 39,590 km
            // -------------------------------------------------------------
            $this->info('5. Seeding 3 Dispatches for SHA 1247 (2 Patient Transports + 1 Admin)...');

            $dispatches = [
                // Dispatch 1: Patient Transport
                [
                    'mission_type' => Dispatch::MISSION_OTHERS,
                    'mission_other' => 'Patient Transport',
                    'location' => 'Brgy. San Policarpo Health Station to Calbayog District Hospital',
                    'date' => '2026-09-30',
                    'time_out' => '10:15:00',
                    'time_in' => '11:45:00',
                    'odo_out' => 39520,
                    'odo_in' => 39545,
                    'remarks' => 'Emergency patient transport of pediatric patient with severe dehydration to District Hospital ER. Oxygen administered en route.',
                ],
                // Dispatch 2: Patient Transport
                [
                    'mission_type' => Dispatch::MISSION_OTHERS,
                    'mission_other' => 'Patient Transport',
                    'location' => 'Brgy. Dagum Rural Health Unit to Calbayog District Hospital OB Ward',
                    'date' => '2026-10-07',
                    'time_out' => '13:30:00',
                    'time_in' => '15:15:00',
                    'odo_out' => 39545,
                    'odo_in' => 39572,
                    'remarks' => 'Urgent maternal patient transfer to District Hospital delivery room. Vital signs continuously monitored.',
                ],
                // Dispatch 3: Administrative Travel
                [
                    'mission_type' => 'Administrative Travel',
                    'mission_other' => null,
                    'location' => 'CHO Compound to City Hall GSO Motorpool Supply Office',
                    'date' => '2026-10-14',
                    'time_out' => '09:30:00',
                    'time_in' => '11:15:00',
                    'odo_out' => 39572,
                    'odo_in' => 39590,
                    'remarks' => 'Routine administrative logistics run to replenish emergency medical oxygen tanks and PPE supply stock for the ambulance fleet.',
                ],
            ];

            foreach ($dispatches as $dsp) {
                DB::table('dispatches')->insert([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $v1247->id,
                    'driver_id' => $serrato->id,
                    'second_driver_id' => null,
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

            // Sync SHA 1247 vehicle current mileage and operational status
            DB::table('vehicles')
                ->where('id', $v1247->id)
                ->update([
                    'current_mileage' => 39590,
                    'status' => Vehicle::STATUS_OPERATIONAL,
                    'updated_at' => '2026-10-14 11:15:00',
                ]);

            // -------------------------------------------------------------
            // 6. HISTORICAL NOTIFICATIONS (Direct DB Inserts with Exact Past Timestamps)
            // -------------------------------------------------------------
            $this->info('6. Seeding Chronological Notifications for Serrato and Admin...');

            $notifs = [
                // Oct 02, 2026 09:20 AM — Admin: Flagged inspection on SHA 1247 (Water)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'SHA 1247 — 1 item flagged: Water (radiator coolant reserve topped up sa motorpool).',
                    'data' => json_encode(['vehicle_id' => $v1247->id, 'plate_number' => 'SHA 1247', 'driver' => 'Reynaldo Serrato']),
                    'created_at' => '2026-10-02 09:20:00',
                ],
                // Oct 08, 2026 10:30 AM — Admin: New Damage Report Submitted by Serrato
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                    'title' => 'New Damage Report Submitted',
                    'message' => 'Reynaldo Serrato reported sticky cabin sliding door latch on SHA 1247.',
                    'data' => json_encode(['vehicle_id' => $v1247->id, 'plate_number' => 'SHA 1247', 'driver' => 'Reynaldo Serrato']),
                    'created_at' => '2026-10-08 10:30:00',
                ],
                // Oct 10, 2026 08:30 AM — Driver Serrato: PM Reminder Due Soon
                [
                    'user_id' => $serrato->id,
                    'type' => Notification::TYPE_PM_REMINDER,
                    'title' => 'Preventive Maintenance Approaching',
                    'message' => 'SHA 1247: Semi-Annual Ambulance PMS is scheduled for Oct 31, 2026.',
                    'data' => json_encode(['vehicle_id' => $v1247->id, 'plate_number' => 'SHA 1247', 'service_target' => 'Semi-Annual Ambulance PMS']),
                    'created_at' => '2026-10-10 08:30:00',
                ],
                // Oct 10, 2026 08:30 AM — Admin: PM Due Soon Alert on SHA 1247
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_PM_DUE_SOON,
                    'title' => 'Preventive Maintenance Due Soon',
                    'message' => 'SHA 1247 is within 21 days Due Soon threshold for Semi-Annual Ambulance PMS (due Oct 31, 2026).',
                    'data' => json_encode(['vehicle_id' => $v1247->id, 'plate_number' => 'SHA 1247', 'service_target' => 'Semi-Annual Ambulance PMS']),
                    'created_at' => '2026-10-10 08:30:00',
                ],
                // Oct 12, 2026 09:25 AM — Admin: Flagged inspection on SHA 1247 (Tires)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => 'SHA 1247 — 1 item flagged: Tires (low front left tire pressure resolved sa Shell).',
                    'data' => json_encode(['vehicle_id' => $v1247->id, 'plate_number' => 'SHA 1247', 'driver' => 'Reynaldo Serrato']),
                    'created_at' => '2026-10-12 09:25:00',
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

        $this->info('Successfully seeded authentic historical UAT data for Reynaldo Serrato & SHA 1247!');
        $this->table(
            ['Entity', 'Count for SHA 1247 / Serrato'],
            [
                ['Inspections', DB::table('inspections')->where('vehicle_id', $v1247->id)->count()],
                ['Inspection Items', DB::table('inspection_items')->whereIn('inspection_id', DB::table('inspections')->where('vehicle_id', $v1247->id)->pluck('id'))->count()],
                ['Damage Reports', DB::table('damage_reports')->where('vehicle_id', $v1247->id)->count()],
                ['Repair Logs', DB::table('repair_logs')->where('vehicle_id', $v1247->id)->count()],
                ['PM Schedules', DB::table('pm_schedules')->where('vehicle_id', $v1247->id)->count()],
                ['Dispatches', DB::table('dispatches')->where('vehicle_id', $v1247->id)->count()],
                ['Serrato Notifications', DB::table('notifications')->where('user_id', $serrato->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

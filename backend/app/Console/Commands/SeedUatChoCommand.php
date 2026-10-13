<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\InspectionItem;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed authentic, realistic historical UAT data for the City Health Office (CHO).
 *
 * Rules:
 * - Dates: September 14, 2026 to October 20, 2026 (plus earlier 2026 repairs/PMs).
 * - Weekdays only (no Saturday/Sunday).
 * - Working hours: 9:00 AM to 5:00 PM.
 * - Language: Simple English mixed with authentic Calbayognon Waray-waray.
 * - Reflects existing live Railway vehicles (SHA 1247, SHA 1248, SHA 2625) and drivers.
 * - Visible to BOTH Web Admin and Driver Mobile App.
 */
class SeedUatChoCommand extends Command
{
    protected $signature = 'rvms:seed-uat-cho {--fresh : Wipe previously seeded CHO UAT data before seeding}';

    protected $description = 'Seed authentic, realistic historical UAT records for City Health Office (CHO)';

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
        $v1248 = Vehicle::where('agency_id', $agency->id)->where('plate_number', 'SHA 1248')->first();
        $v2625 = Vehicle::where('agency_id', $agency->id)->where('plate_number', 'SHA 2625')->first();

        if (! $v1247 || ! $v1248 || ! $v2625) {
            $this->error('One or more CHO vehicles (SHA 1247, SHA 1248, SHA 2625) missing from database.');
            return self::FAILURE;
        }

        $driverSerrato = User::where('email', 'serrato.cho@email.com')->first();
        $driverCanaman = User::where('email', 'canaman.cho@email.com')->first();
        $driverGamao   = User::where('email', 'gamao.cho@email.com')->first();
        $driverBalloca = User::where('email', 'balloca.cho@email.com')->first();

        if (! $driverSerrato || ! $driverCanaman || ! $driverGamao || ! $driverBalloca) {
            $this->error('One or more CHO drivers missing from database.');
            return self::FAILURE;
        }

        $checklists = InspectionChecklistItem::forAgencyCode('CHO')->get();
        if ($checklists->isEmpty()) {
            $this->error('BLOWBAGETS checklist items catalog is empty.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->info('Cleaning up existing CHO UAT records...');
            InspectionItem::whereHas('inspection', fn ($q) => $q->where('agency_id', $agency->id))->delete();
            Inspection::where('agency_id', $agency->id)->delete();
            DamageReport::where('agency_id', $agency->id)->delete();
            RepairLog::where('agency_id', $agency->id)->delete();
            PmSchedule::where('agency_id', $agency->id)->delete();
            Dispatch::where('agency_id', $agency->id)->delete();
            Notification::where('agency_id', $agency->id)->delete();
            $this->info('Previous records cleared.');
        }

        DB::transaction(function () use (
            $agency, $admin, $v1247, $v1248, $v2625,
            $driverSerrato, $driverCanaman, $driverGamao, $driverBalloca,
            $checklists
        ) {
            // -------------------------------------------------------------
            // 1. REPAIR LOGS (Earlier 2026 history + UAT resolution)
            // -------------------------------------------------------------
            $this->info('Seeding Repair Logs...');

            // Mar 18, 2026 — SHA 1247 (Ambulance 1) Brake pads overhaul at GSO
            RepairLog::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1247->id,
                'driver_id' => $driverSerrato->id,
                'repair_date' => '2026-03-18',
                'scope_of_work' => 'Replaced front brake pads and bled front brake lines after reported squeal during emergency response.',
                'parts_replaced' => 'Front brake pad set (OEM Toyota), DOT 3 brake fluid 1L',
                'cost' => 3200.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Endorsed to GSO Motorpool mechanics. Road tested safe with balanced stopping distance.',
                'created_at' => Carbon::parse('2026-03-18 14:30:00'),
                'updated_at' => Carbon::parse('2026-03-18 14:30:00'),
            ]);

            // Jun 10, 2026 — SHA 1248 (Ambulance 2) Motolite Battery replacement
            RepairLog::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1248->id,
                'driver_id' => $driverCanaman->id,
                'repair_date' => '2026-06-10',
                'scope_of_work' => 'Battery failed load test during morning engine cranking. Replaced with new maintenance-free heavy duty unit.',
                'parts_replaced' => 'Motolite Gold 12V 70AH Battery (Commercial Grade)',
                'cost' => 6850.00,
                'repair_source' => RepairLog::SOURCE_EXTERNAL,
                'external_shop_name' => 'Motolite Express Service Station - Calbayog Branch',
                'receipt_path' => 'receipts/cho_battery_or_20260610.pdf',
                'remarks' => 'Purchased under emergency purchase order. Official receipt filed with CHO logistics.',
                'created_at' => Carbon::parse('2026-06-10 11:15:00'),
                'updated_at' => Carbon::parse('2026-06-10 11:15:00'),
            ]);

            // Aug 14, 2026 — SHA 2625 (Service Hilux) Stabilizer link bushings at GSO
            RepairLog::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2625->id,
                'driver_id' => $driverGamao->id,
                'repair_date' => '2026-08-14',
                'scope_of_work' => 'Replaced worn front suspension stabilizer link rubber bushings to eliminate clunking noise over rough roads.',
                'parts_replaced' => 'Front stabilizer link bushings (4 pcs)',
                'cost' => 1800.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Work completed by GSO Motorpool under Job Order #CHO-2026-08-019.',
                'created_at' => Carbon::parse('2026-08-14 15:45:00'),
                'updated_at' => Carbon::parse('2026-08-14 15:45:00'),
            ]);

            // Sep 28, 2026 — SHA 1248 (Ambulance 2) Freon Leak & AC Repair (resolves Sep 23 damage report)
            RepairLog::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1248->id,
                'driver_id' => $driverCanaman->id,
                'repair_date' => '2026-09-28',
                'scope_of_work' => 'Checked AC system leak on rear patient cabin evaporator. Replaced high-pressure rubber O-ring seal, vacuumed lines, and recharged freon.',
                'parts_replaced' => 'R134a Refrigerant (2 cans), AC line high-pressure O-ring seal, cabin pollen filter',
                'cost' => 2400.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Aircon temperature tested at 18C in rear compartment under midday sun. Unit returned to Operational status.',
                'created_at' => Carbon::parse('2026-09-28 14:00:00'),
                'updated_at' => Carbon::parse('2026-09-28 14:00:00'),
            ]);

            // -------------------------------------------------------------
            // 2. PREVENTIVE MAINTENANCE SCHEDULES
            // -------------------------------------------------------------
            $this->info('Seeding PM Schedules...');

            // SHA 1247 — Completed 35,000 km PMS
            PmSchedule::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1247->id,
                'service_target' => '35,000 km Periodic Maintenance Service (PMS)',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 30000,
                'due_mileage' => 35000,
                'due_date' => null,
                'due_soon_threshold_km' => 500,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_COMPLETED,
                'date_serviced' => '2026-09-18',
                'completion_mileage' => 39500,
                'completion_repair_source' => RepairLog::SOURCE_GSO,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => 'Fully synthetic engine oil 15W-40 (7L), OEM oil filter, air filter cleaned',
                'completion_remarks' => 'Completed scheduled PMS at GSO Motorpool. All fluid levels and belts inspected.',
                'created_at' => Carbon::parse('2026-09-14 09:30:00'),
                'updated_at' => Carbon::parse('2026-09-18 16:00:00'),
            ]);

            // SHA 1247 — Upcoming 40,000 km PMS
            PmSchedule::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1247->id,
                'service_target' => '40,000 km Periodic Maintenance Service (PMS)',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 39500,
                'due_mileage' => 44500,
                'due_date' => null,
                'due_soon_threshold_km' => 500,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_UPCOMING,
                'date_serviced' => null,
                'completion_mileage' => null,
                'completion_repair_source' => null,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => null,
                'completion_remarks' => null,
                'created_at' => Carbon::parse('2026-09-18 16:30:00'),
                'updated_at' => Carbon::parse('2026-09-18 16:30:00'),
            ]);

            // SHA 1248 — Completed Time-based 6-month check
            PmSchedule::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1248->id,
                'service_target' => 'Semi-Annual Electrical, Battery & Fluid Inspection',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_TIME,
                'interval_km' => null,
                'last_pm_mileage' => 45000,
                'due_mileage' => null,
                'due_date' => '2026-08-15',
                'due_soon_threshold_km' => null,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_COMPLETED,
                'date_serviced' => '2026-08-15',
                'completion_mileage' => 46800,
                'completion_repair_source' => RepairLog::SOURCE_GSO,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => 'Coolant fluid flush, terminal grease, wiper fluid',
                'completion_remarks' => 'Completed routine semi-annual check. Electrical harnesses verified intact.',
                'created_at' => Carbon::parse('2026-08-01 10:00:00'),
                'updated_at' => Carbon::parse('2026-08-15 15:00:00'),
            ]);

            // SHA 1248 — Active DUE SOON PM (Matches current "Under Preventive Maintenance" status!)
            PmSchedule::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1248->id,
                'service_target' => '47,000 km Brake System Overhaul & Suspension Safety Check',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 42000,
                'due_mileage' => 47000,
                'due_date' => null,
                'due_soon_threshold_km' => 500,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_DUE_SOON,
                'date_serviced' => null,
                'completion_mileage' => null,
                'completion_repair_source' => null,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => null,
                'completion_remarks' => null,
                'created_at' => Carbon::parse('2026-09-29 10:00:00'),
                'updated_at' => Carbon::parse('2026-10-13 01:02:55'),
            ]);

            // SHA 2625 — Completed Heavy Drivetrain Check
            PmSchedule::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2625->id,
                'service_target' => '55,000 km Drivetrain & Differential Fluid Inspection',
                'schedule_document_path' => null,
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 10000,
                'last_pm_mileage' => 45000,
                'due_mileage' => 55000,
                'due_date' => null,
                'due_soon_threshold_km' => 500,
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_COMPLETED,
                'date_serviced' => '2026-07-22',
                'completion_mileage' => 55100,
                'completion_repair_source' => RepairLog::SOURCE_GSO,
                'completion_external_shop_name' => null,
                'completion_receipt_path' => null,
                'completion_parts_replaced' => 'Differential gear oil 80W-90 (3L), transfer case fluid',
                'completion_remarks' => 'Completed at GSO Motorpool. 4WD engagement tested smooth.',
                'created_at' => Carbon::parse('2026-07-10 09:00:00'),
                'updated_at' => Carbon::parse('2026-07-22 14:00:00'),
            ]);

            // -------------------------------------------------------------
            // 3. DAMAGE REPORTS
            // -------------------------------------------------------------
            $this->info('Seeding Damage Reports...');

            // Sep 23, 2026 — SHA 1248 (Robert Canaman) AC Weakness (Resolved on Sep 28)
            DamageReport::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v1248->id,
                'driver_id' => $driverCanaman->id,
                'nature_of_damage' => 'Aircon sa patient compartment diri na kaayo mabugnaw labi na kon udto. Medyo maalinsangan para sa pasyente sa luyo.',
                'suspected_parts' => 'AC freon leak or blower relay',
                'photo_path' => null,
                'date_reported' => '2026-09-23',
                'status' => DamageReport::STATUS_REVIEWED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => Carbon::parse('2026-09-24 09:15:00'),
                'created_at' => Carbon::parse('2026-09-23 10:30:00'),
                'updated_at' => Carbon::parse('2026-09-24 09:15:00'),
            ]);

            // Oct 12, 2026 — SHA 2625 (Rowel Gamao) Clutch slipping (Explains current "Not Operational"!)
            DamageReport::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $v2625->id,
                'driver_id' => $driverGamao->id,
                'nature_of_damage' => 'Medyo nag-iislayd an clutch pag tungas ha bukid san Brgy. Obrero kanina. Makuri isulod an primera ngan segunda kambio kon mainit makina.',
                'suspected_parts' => 'Clutch lining / pressure plate wear',
                'photo_path' => null,
                'date_reported' => '2026-10-12',
                'status' => DamageReport::STATUS_REVIEWED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => Carbon::parse('2026-10-13 09:00:00'),
                'created_at' => Carbon::parse('2026-10-12 11:15:00'),
                'updated_at' => Carbon::parse('2026-10-13 09:00:00'),
            ]);

            // -------------------------------------------------------------
            // 4. DAILY BLOWBAGETS INSPECTIONS
            // -------------------------------------------------------------
            $this->info('Seeding Daily Inspections...');

            $inspectionsData = [
                // SHA 1247 (Reynaldo Serrato)
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'date' => '2026-09-15',
                    'time' => '09:15:00',
                    'review_time' => '11:00:00',
                    'flagged_item' => null,
                    'remarks' => 'All lights, siren, and oxygen gauge working. Maupay an dalagan san makina.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'date' => '2026-09-21',
                    'time' => '09:20:00',
                    'review_time' => '11:15:00',
                    'flagged_item' => null,
                    'remarks' => 'Waray aberya. Checked engine oil and brake fluid, ready for duty standby.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'date' => '2026-09-25',
                    'time' => '09:10:00',
                    'review_time' => '11:30:00',
                    'flagged_item' => 'Tires',
                    'flagged_note' => 'Medyo humok an ligid sa unahan (front left). Gin-pahanginan ko 32 psi sa Shell kanina.',
                    'remarks' => 'Good condition an ambulance, humok la gamay an front left tire pero nahanginan na.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'date' => '2026-10-01',
                    'time' => '09:30:00',
                    'review_time' => '13:30:00',
                    'flagged_item' => null,
                    'remarks' => 'Good condition an unit. Functional an mga emergency lights para ha patient transfer.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'date' => '2026-10-08',
                    'time' => '09:15:00',
                    'review_time' => '10:45:00',
                    'flagged_item' => 'Water',
                    'flagged_note' => 'Guti-ay na la an tubig sa coolant reservoir. Ginsudlan ko distilled water.',
                    'remarks' => 'Topped up radiator reserve tank. Ready for response standby.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'date' => '2026-10-14',
                    'time' => '09:25:00',
                    'review_time' => '11:20:00',
                    'flagged_item' => null,
                    'remarks' => 'Ready an ambulance for morning dispatch. Bugnaw an aircon ngan full tank.',
                ],

                // SHA 1248 (Robert Canaman)
                [
                    'vehicle' => $v1248,
                    'driver' => $driverCanaman,
                    'date' => '2026-09-16',
                    'time' => '09:20:00',
                    'review_time' => '11:00:00',
                    'flagged_item' => null,
                    'remarks' => 'Checked fluids and battery, functional an siren ngan stretcher mechanism.',
                ],
                [
                    'vehicle' => $v1248,
                    'driver' => $driverCanaman,
                    'date' => '2026-09-22',
                    'time' => '09:15:00',
                    'review_time' => '11:30:00',
                    'flagged_item' => null,
                    'remarks' => 'Waray problema ha dalagan. Ready for patient transport.',
                ],
                [
                    'vehicle' => $v1248,
                    'driver' => $driverCanaman,
                    'date' => '2026-09-29',
                    'time' => '09:35:00',
                    'review_time' => '13:15:00',
                    'flagged_item' => 'Brakes',
                    'flagged_note' => 'Medyo naglalagtok kon itamak an preno pero kumakapit pa man. Pa-check nala sa motorpool pag may schedule.',
                    'remarks' => 'Preno medyo naglalagtok sa ubos, pero ok pa brake bite.',
                ],
                [
                    'vehicle' => $v1248,
                    'driver' => $driverCanaman,
                    'date' => '2026-10-05',
                    'time' => '09:10:00',
                    'review_time' => '10:45:00',
                    'flagged_item' => null,
                    'remarks' => 'All electricals and blinkers working. Maupay dalagan san makina.',
                ],
                [
                    'vehicle' => $v1248,
                    'driver' => $driverCanaman,
                    'date' => '2026-10-09',
                    'time' => '09:40:00',
                    'review_time' => '11:30:00',
                    'flagged_item' => 'Brakes',
                    'flagged_note' => 'Medyo humok na an apak san preno, kinahanglan na i-bleed ngan palitan fluid ha motorpool.',
                    'remarks' => 'Recommended for scheduled PMS brake checkup this month.',
                ],

                // SHA 2625 (Rowel Gamao)
                [
                    'vehicle' => $v2625,
                    'driver' => $driverGamao,
                    'date' => '2026-09-17',
                    'time' => '09:15:00',
                    'review_time' => '11:15:00',
                    'flagged_item' => null,
                    'remarks' => 'Cargo bed cleared. Checked 4x4, tire pressure, and engine oil. Ready for field outreach.',
                ],
                [
                    'vehicle' => $v2625,
                    'driver' => $driverGamao,
                    'date' => '2026-09-24',
                    'time' => '09:30:00',
                    'review_time' => '11:45:00',
                    'flagged_item' => null,
                    'remarks' => 'Maupay an dalagan san makina. Complete safety tools and spare tire.',
                ],
                [
                    'vehicle' => $v2625,
                    'driver' => $driverGamao,
                    'date' => '2026-10-02',
                    'time' => '09:20:00',
                    'review_time' => '11:00:00',
                    'flagged_item' => null,
                    'remarks' => 'All fluids ok, headlight and signal lights bright. Ready for field transport.',
                ],
                [
                    'vehicle' => $v2625,
                    'driver' => $driverGamao,
                    'date' => '2026-10-07',
                    'time' => '09:10:00',
                    'review_time' => '10:30:00',
                    'flagged_item' => null,
                    'remarks' => 'Good condition an service pickup. Ready for vaccination logistics.',
                ],
            ];

            foreach ($inspectionsData as $data) {
                $insp = Inspection::create([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $data['vehicle']->id,
                    'driver_id' => $data['driver']->id,
                    'inspection_date' => $data['date'],
                    'review_status' => Inspection::STATUS_REVIEWED,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => Carbon::parse("{$data['date']} {$data['review_time']}"),
                    'created_at' => Carbon::parse("{$data['date']} {$data['time']}"),
                    'updated_at' => Carbon::parse("{$data['date']} {$data['review_time']}"),
                ]);

                foreach ($checklists as $chk) {
                    $hasIssue = ($data['flagged_item'] && strcasecmp($chk->name, $data['flagged_item']) === 0);
                    InspectionItem::create([
                        'inspection_id' => $insp->id,
                        'checklist_item_id' => $chk->id,
                        'status' => $hasIssue ? InspectionItem::STATUS_HAS_ISSUE : InspectionItem::STATUS_OK,
                        'remarks' => $hasIssue ? $data['flagged_note'] : null,
                    ]);
                }
            }

            // -------------------------------------------------------------
            // 5. DISPATCHES (Progressive Odometer Curves)
            // -------------------------------------------------------------
            $this->info('Seeding Dispatches...');

            $dispatchesData = [
                // SHA 1247 (starts 39,810 km -> 39,980 km)
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'Calbayog District Hospital, Brgy. Dagum',
                    'date' => '2026-09-15',
                    'time_out' => '10:15:00',
                    'time_in' => '11:45:00',
                    'odo_out' => 39810,
                    'odo_in' => 39832,
                    'remarks' => 'Emergency transfer of hypertension patient from CHO compound to District Hospital ER.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'Brgy. San Policarpo Health Center to Calbayog District Hospital',
                    'date' => '2026-09-21',
                    'time_out' => '13:00:00',
                    'time_in' => '14:45:00',
                    'odo_out' => 39832,
                    'odo_in' => 39858,
                    'remarks' => 'Maternal emergency patient transfer to District Hospital OB ward. Transported safely.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'Brgy. Matobato to St. Camillus Hospital',
                    'date' => '2026-09-25',
                    'time_out' => '10:30:00',
                    'time_in' => '12:00:00',
                    'odo_out' => 39858,
                    'odo_in' => 39886,
                    'remarks' => 'Vehicular trauma patient transfer from highway scene to St. Camillus Hospital ER.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'Nijaga Park, Calbayog Commercial District',
                    'date' => '2026-10-01',
                    'time_out' => '09:45:00',
                    'time_in' => '13:30:00',
                    'odo_out' => 39886,
                    'odo_in' => 39912,
                    'remarks' => 'Medical standby team during public civic parade and senior citizens health screening.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'Calbayog District Hospital to Carayman Junction',
                    'date' => '2026-10-08',
                    'time_out' => '11:15:00',
                    'time_in' => '13:00:00',
                    'odo_out' => 39912,
                    'odo_in' => 39948,
                    'remarks' => 'Inter-facility patient transfer rendezvous with Samar Provincial Hospital ambulance.',
                ],
                [
                    'vehicle' => $v1247,
                    'driver' => $driverSerrato,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'Brgy. Obrero Health Center to CHO Compound',
                    'date' => '2026-10-14',
                    'time_out' => '10:00:00',
                    'time_in' => '11:45:00',
                    'odo_out' => 39948,
                    'odo_in' => 39980,
                    'remarks' => 'Emergency transfer of pediatric patient with severe dehydration to CHO clinic.',
                ],

                // SHA 1248 (starts 47,150 km -> 47,250 km)
                [
                    'vehicle' => $v1248,
                    'driver' => $driverCanaman,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'Brgy. Rawis to Calbayog District Hospital',
                    'date' => '2026-09-16',
                    'time_out' => '10:00:00',
                    'time_in' => '11:30:00',
                    'odo_out' => 47150,
                    'odo_in' => 47174,
                    'remarks' => 'Transported respiratory distress patient from coastal barangay to District Hospital.',
                ],
                [
                    'vehicle' => $v1248,
                    'driver' => $driverCanaman,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'CHO Compound to St. Camillus Hospital',
                    'date' => '2026-09-22',
                    'time_out' => '13:30:00',
                    'time_in' => '15:00:00',
                    'odo_out' => 47174,
                    'odo_in' => 47202,
                    'remarks' => 'Scheduled dialysis patient transfer. Mission completed without incident.',
                ],
                [
                    'vehicle' => $v1248,
                    'driver' => $driverCanaman,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'Calbayog Port Area to Calbayog District Hospital',
                    'date' => '2026-10-05',
                    'time_out' => '11:00:00',
                    'time_in' => '12:30:00',
                    'odo_out' => 47202,
                    'odo_in' => 47226,
                    'remarks' => 'Medical response assistance for passenger injured at seaport terminal.',
                ],
                [
                    'vehicle' => $v1248,
                    'driver' => $driverCanaman,
                    'second_driver' => null,
                    'type' => 'Medical Response',
                    'location' => 'Brgy. Dagum to St. Camillus Hospital',
                    'date' => '2026-10-09',
                    'time_out' => '14:00:00',
                    'time_in' => '15:45:00',
                    'odo_out' => 47226,
                    'odo_in' => 47250,
                    'remarks' => 'Cardiac emergency patient transfer. Patient safely admitted to ICU.',
                ],

                // SHA 2625 (starts 58,280 km -> 58,400 km, Primary: Rowel Gamao, Secondary: Idelfonso Balloca)
                [
                    'vehicle' => $v2625,
                    'driver' => $driverGamao,
                    'second_driver' => $driverBalloca,
                    'type' => 'Administrative Travel',
                    'location' => 'Brgy. San Policarpo Health Station',
                    'date' => '2026-09-17',
                    'time_out' => '09:30:00',
                    'time_in' => '12:00:00',
                    'odo_out' => 58280,
                    'odo_in' => 58315,
                    'remarks' => 'Delivered monthly vaccine supplies and cold-chain containers to barangay health workers.',
                ],
                [
                    'vehicle' => $v2625,
                    'driver' => $driverGamao,
                    'second_driver' => $driverBalloca,
                    'type' => 'Administrative Travel',
                    'location' => 'Brgy. Dagum & Brgy. Matobato Health Centers',
                    'date' => '2026-09-24',
                    'time_out' => '13:00:00',
                    'time_in' => '16:00:00',
                    'odo_out' => 58315,
                    'odo_in' => 58350,
                    'remarks' => 'Transported Dengue vector control misting equipment and larvicide chemicals.',
                ],
                [
                    'vehicle' => $v2625,
                    'driver' => $driverGamao,
                    'second_driver' => $driverBalloca,
                    'type' => 'Administrative Travel',
                    'location' => 'City Hall Motorpool & GSO Supply Office',
                    'date' => '2026-10-02',
                    'time_out' => '10:00:00',
                    'time_in' => '11:45:00',
                    'odo_out' => 58350,
                    'odo_in' => 58372,
                    'remarks' => 'Hauled emergency medical supply boxes and medical record logbooks from City Hall.',
                ],
                [
                    'vehicle' => $v2625,
                    'driver' => $driverGamao,
                    'second_driver' => $driverBalloca,
                    'type' => 'Administrative Travel',
                    'location' => 'Brgy. Obrero Rural Health Unit',
                    'date' => '2026-10-07',
                    'time_out' => '09:30:00',
                    'time_in' => '12:30:00',
                    'odo_out' => 58372,
                    'odo_in' => 58400,
                    'remarks' => 'Delivered rural health maternal kits and nutrition supplies to upland health center.',
                ],
            ];

            foreach ($dispatchesData as $disp) {
                Dispatch::create([
                    'agency_id' => $agency->id,
                    'vehicle_id' => $disp['vehicle']->id,
                    'driver_id' => $disp['driver']->id,
                    'second_driver_id' => $disp['second_driver'] ? $disp['second_driver']->id : null,
                    'mission_type' => $disp['type'],
                    'mission_other' => null,
                    'location' => $disp['location'],
                    'time_out' => Carbon::parse("{$disp['date']} {$disp['time_out']}"),
                    'travel_order_path' => null,
                    'odometer_out' => $disp['odo_out'],
                    'time_in' => Carbon::parse("{$disp['date']} {$disp['time_in']}"),
                    'odometer_in' => $disp['odo_in'],
                    'return_status' => Vehicle::STATUS_OPERATIONAL,
                    'remarks' => $disp['remarks'],
                    'created_at' => Carbon::parse("{$disp['date']} {$disp['time_out']}"),
                    'updated_at' => Carbon::parse("{$disp['date']} {$disp['time_in']}"),
                ]);
            }

            // -------------------------------------------------------------
            // 6. IN-APP & MOBILE NOTIFICATIONS (Both Admin and Drivers)
            // -------------------------------------------------------------
            $this->info('Seeding Notifications for Drivers and Admin...');

            // Status change notifications for drivers
            $notifs = [
                // For driver Robert Canaman (SHA 1248 set to Under PM on Oct 13)
                [
                    'user_id' => $driverCanaman->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 1248 (Ambulance) status changed to Under Preventive Maintenance.',
                    'data' => ['vehicle_id' => $v1248->id, 'plate_number' => 'SHA 1248', 'new_status' => 'Under Preventive Maintenance'],
                    'created_at' => '2026-10-13 01:02:55',
                ],
                // For driver Rowel Gamao (SHA 2625 set to Not Operational on Oct 13)
                [
                    'user_id' => $driverGamao->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 (Service Vehicle) status changed to Not Operational.',
                    'data' => ['vehicle_id' => $v2625->id, 'plate_number' => 'SHA 2625', 'new_status' => 'Not Operational'],
                    'created_at' => '2026-10-13 01:02:07',
                ],
                // For secondary driver Idelfonso Balloca (SHA 2625 status notification parity)
                [
                    'user_id' => $driverBalloca->id,
                    'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
                    'title' => 'Vehicle Status Updated',
                    'message' => 'SHA 2625 (Service Vehicle) status changed to Not Operational.',
                    'data' => ['vehicle_id' => $v2625->id, 'plate_number' => 'SHA 2625', 'new_status' => 'Not Operational'],
                    'created_at' => '2026-10-13 01:02:07',
                ],
                // For driver Robert Canaman (PM Due Soon Alert)
                [
                    'user_id' => $driverCanaman->id,
                    'type' => Notification::TYPE_PM_REMINDER,
                    'title' => 'Preventive Maintenance Due Soon',
                    'message' => 'SHA 1248: 47,000 km Brake System Overhaul is Due Soon.',
                    'data' => ['vehicle_id' => $v1248->id, 'plate_number' => 'SHA 1248', 'service_target' => '47,000 km Brake System Overhaul'],
                    'created_at' => '2026-10-13 08:30:00',
                ],
                // For CHO Admin (Damage Report submitted on Oct 12)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                    'title' => 'Damage Report Submitted',
                    'message' => 'Rowel Gamao reported clutch wear on SHA 2625.',
                    'data' => ['vehicle_id' => $v2625->id, 'plate_number' => 'SHA 2625', 'driver' => 'Rowel Gamao'],
                    'created_at' => '2026-10-12 11:15:00',
                ],
                // For CHO Admin (PM Due Soon on SHA 1248)
                [
                    'user_id' => $admin->id,
                    'type' => Notification::TYPE_PM_DUE_SOON,
                    'title' => 'Preventive Maintenance Due Soon',
                    'message' => 'SHA 1248 is due soon for 47,000 km Brake System Overhaul.',
                    'data' => ['vehicle_id' => $v1248->id, 'plate_number' => 'SHA 1248'],
                    'created_at' => '2026-10-13 08:30:00',
                ],
            ];

            foreach ($notifs as $n) {
                Notification::create([
                    'agency_id' => $agency->id,
                    'user_id' => $n['user_id'],
                    'type' => $n['type'],
                    'title' => $n['title'],
                    'message' => $n['message'],
                    'data' => $n['data'],
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => Carbon::parse($n['created_at']),
                    'updated_at' => Carbon::parse($n['created_at']),
                ]);
            }
        });

        $this->info('Successfully seeded authentic historical UAT data for City Health Office (CHO)!');
        $this->table(
            ['Entity', 'Count for CHO'],
            [
                ['Inspections', Inspection::where('agency_id', $agency->id)->count()],
                ['Inspection Items', InspectionItem::whereHas('inspection', fn ($q) => $q->where('agency_id', $agency->id))->count()],
                ['Damage Reports', DamageReport::where('agency_id', $agency->id)->count()],
                ['Repair Logs', RepairLog::where('agency_id', $agency->id)->count()],
                ['PM Schedules', PmSchedule::where('agency_id', $agency->id)->count()],
                ['Dispatches', Dispatch::where('agency_id', $agency->id)->count()],
                ['Notifications', Notification::where('agency_id', $agency->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

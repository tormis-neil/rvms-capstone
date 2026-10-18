<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\RepairLog;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed authentic historical repair logs for unassigned vehicle SHA-7901 (PNP).
 *
 * Rules:
 * - Direct DB::table inserts to preserve past timestamps (Rule 1).
 * - Repair source must use valid constants: RepairLog::SOURCE_GSO, RepairLog::SOURCE_EXTERNAL (Rule 6).
 * - Idempotency: Cleans up previous logs for SHA-7901 before seeding (Rule 7).
 * - Strictly null dummy attachments (Rule 8).
 * - Vehicle remains Operational without damage tickets (unassigned fleet) (Rule 9).
 * - Pre-operational created_at calibration to Sep 14, 2026 (Rule 11).
 */
class SeedPnpUnassignedCommand extends Command
{
    protected $signature = 'rvms:seed-pnp-unassigned';

    protected $description = 'Seed authentic historical repair logs for unassigned vehicle SHA-7901 (PNP)';

    public function handle(): int
    {
        $this->info('Starting historical repair logs seeding for unassigned vehicle SHA-7901 (PNP)...');

        $agency = Agency::where('code', 'PNP')->first();
        if (! $agency) {
            $this->error('PNP agency record not found!');
            return self::FAILURE;
        }

        $v7901 = Vehicle::where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'SHA-7901')
                  ->orWhere('plate_number', 'SHA 7901');
            })->first();

        if (! $v7901) {
            $this->info('Vehicle SHA-7901 not found; creating vehicle record...');
            $v7901 = Vehicle::create([
                'agency_id' => $agency->id,
                'plate_number' => 'SHA-7901',
                'type' => 'Utility Patrol',
                'make' => 'Isuzu',
                'model' => 'D-Max',
                'engine_number' => '4JJ1-TC-774912',
                'chassis_number' => 'MPATFS85H7H517901',
                'current_mileage' => 68450,
                'status' => Vehicle::STATUS_OPERATIONAL,
                'assigned_driver_id' => null,
                'secondary_driver_id' => null,
                'created_at' => '2026-09-14 08:45:00',
                'updated_at' => '2026-09-14 08:45:00',
            ]);
        }

        $this->info('Cleaning up existing repair logs for SHA-7901 to ensure idempotency...');
        DB::table('repair_logs')
            ->where('agency_id', $agency->id)
            ->where('vehicle_id', $v7901->id)
            ->delete();

        DB::transaction(function () use ($agency, $v7901) {
            // -------------------------------------------------------------
            // 0. ACCOUNT & VEHICLE CREATION TIMESTAMPS (Rule 11)
            // Ensure unassigned vehicle exists prior to operations
            // -------------------------------------------------------------
            DB::table('vehicles')->where('id', $v7901->id)->update([
                'created_at' => '2026-09-14 08:45:00',
            ]);

            // -------------------------------------------------------------
            // 1. REPAIR LOGS (2 Distinct Historical Repairs: May & August 2026)
            // Unassigned vehicle: driver_id is null; no damage report required
            // -------------------------------------------------------------
            $this->info('1. Seeding 2 historical repair logs for SHA-7901 (May & August 2026)...');

            // Repair 1: May 18, 2026 — GSO Motorpool (Brake System Overhaul)
            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v7901->id,
                'driver_id' => null,
                'repair_date' => '2026-05-18',
                'scope_of_work' => 'Replaced heavily worn front brake pads, serviced front caliper slider pins, and flushed brake hydraulic fluid with DOT 4 fluid.',
                'parts_replaced' => 'Front ceramic brake pad kit (OEM Isuzu), caliper pin dust boots, DOT 4 heavy-duty brake fluid (1L)',
                'cost' => 3600.00,
                'repair_source' => RepairLog::SOURCE_GSO,
                'external_shop_name' => null,
                'receipt_path' => null,
                'remarks' => 'Completed by GSO Motorpool under Job Order #PNP-2026-05-012. Brake squeal eliminated and pedal firmness restored. Certified road-ready.',
                'created_at' => '2026-05-18 14:00:00',
                'updated_at' => '2026-05-18 14:00:00',
            ]);

            // Repair 2: August 25, 2026 — External Repair Shop (Electrical Charging & Relays)
            DB::table('repair_logs')->insert([
                'agency_id' => $agency->id,
                'vehicle_id' => $v7901->id,
                'driver_id' => null,
                'repair_date' => '2026-08-25',
                'scope_of_work' => 'Replaced failing alternator internal voltage regulator and rewired dual rooftop emergency lightbar high-amperage relay circuits.',
                'parts_replaced' => 'Denso 12V alternator IC regulator module, automotive heavy-duty 40A relays (2 pcs)',
                'cost' => 4500.00,
                'repair_source' => RepairLog::SOURCE_EXTERNAL,
                'external_shop_name' => 'Calbayog Auto Electrical & Battery Center',
                'receipt_path' => null,
                'remarks' => 'Charging voltage stabilized at 14.2V under full electrical load. Emergency lightbar circuits inspected and tested safe. Official receipt filed.',
                'created_at' => '2026-08-25 15:30:00',
                'updated_at' => '2026-08-25 15:30:00',
            ]);
        });

        $this->info('Successfully seeded historical repair logs for SHA-7901!');
        $this->table(
            ['Vehicle', 'Repair Logs Count'],
            [
                ['SHA-7901 (Unassigned)', DB::table('repair_logs')->where('vehicle_id', $v7901->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

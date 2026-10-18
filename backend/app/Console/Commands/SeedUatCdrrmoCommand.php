<?php

namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Master historical UAT seeder for the City Disaster Risk Reduction and Management Office (CDRRMO).
 *
 * Orchestrates vehicle and driver seeders to guarantee 100% data parity and adherence to Rules 1-10:
 * 1. SeedCdrrmoJonathanCommand   (Jonathan Dela Peña & CHO-4467 - Patient Transport Vehicle)
 * 2. SeedCdrrmoCalbigaCommand    (Rolando Calbiga & SAB-5120 - Rescue Truck)
 * 3. SeedCdrrmoUnassignedCommand (Unassigned Reserve Vehicles - SHA-2006 & AND-9023)
 */
class SeedUatCdrrmoCommand extends Command
{
    protected $signature = 'rvms:seed-uat-cdrrmo {--fresh : Wipe previously seeded CDRRMO UAT data before seeding}';

    protected $description = 'Seed authentic, realistic historical UAT records across all CDRRMO vehicles and drivers';

    public function handle(): int
    {
        $agency = Agency::where('code', 'CDRRMO')->first();
        if (! $agency) {
            $this->error('CDRRMO agency record not found!');
            return self::FAILURE;
        }

        $this->info('================================================================');
        $this->info('  Starting Master Historical UAT Seeding for CDRRMO');
        $this->info('================================================================');

        // Step 1: Jonathan Dela Peña & CHO-4467 (Patient Transport Vehicle)
        $this->newLine();
        $this->info('>>> [1/3] Seeding Jonathan Dela Peña & CHO-4467...');
        $exitCode = $this->call('rvms:seed-cdrrmo-jonathan', [
            '--fresh' => (bool) $this->option('fresh'),
        ]);
        if ($exitCode !== self::SUCCESS) {
            $this->error('Failed seeding Jonathan Dela Peña & CHO-4467.');
            return $exitCode;
        }

        // Step 2: Rolando Calbiga & SAB-5120 (Rescue Truck)
        $this->newLine();
        $this->info('>>> [2/3] Seeding Rolando Calbiga & SAB-5120...');
        $exitCode = $this->call('rvms:seed-cdrrmo-calbiga');
        if ($exitCode !== self::SUCCESS) {
            $this->error('Failed seeding Rolando Calbiga & SAB-5120.');
            return $exitCode;
        }

        // Step 3: Unassigned Reserve Vehicles (SHA-2006 & AND-9023)
        $this->newLine();
        $this->info('>>> [3/3] Seeding CDRRMO Unassigned Reserve Vehicles (SHA-2006 & AND-9023)...');
        $exitCode = $this->call('rvms:seed-cdrrmo-unassigned');
        if ($exitCode !== self::SUCCESS) {
            $this->error('Failed seeding CDRRMO unassigned vehicles.');
            return $exitCode;
        }

        $this->newLine();
        $this->info('================================================================');
        $this->info('  CDRRMO Historical UAT Seeding Complete across all Vehicles & Drivers');
        $this->info('================================================================');

        $this->table(
            ['Agency Entity', 'Total Count for CDRRMO'],
            [
                ['Inspections', DB::table('inspections')->where('agency_id', $agency->id)->count()],
                ['Inspection Items', DB::table('inspection_items')->whereIn('inspection_id', DB::table('inspections')->where('agency_id', $agency->id)->pluck('id'))->count()],
                ['Damage Reports', DB::table('damage_reports')->where('agency_id', $agency->id)->count()],
                ['Repair Logs', DB::table('repair_logs')->where('agency_id', $agency->id)->count()],
                ['PM Schedules', DB::table('pm_schedules')->where('agency_id', $agency->id)->count()],
                ['Dispatches', DB::table('dispatches')->where('agency_id', $agency->id)->count()],
                ['Notifications', DB::table('notifications')->where('agency_id', $agency->id)->count()],
            ]
        );

        return self::SUCCESS;
    }
}

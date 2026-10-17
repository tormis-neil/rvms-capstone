<?php

namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Master historical UAT seeder for the City Health Office (CHO).
 *
 * Orchestrates driver-specific seeders to guarantee 100% data parity and adherence to Rules 1-10:
 * 1. SeedChoBallocaCommand (Idelfonso Balloca & SHA 2625 - Service Vehicle)
 * 2. SeedChoGamaoCommand   (Rowel Gamao & SHA 2625 - Service Vehicle)
 * 3. SeedChoSerratoCommand (Reynaldo Serrato & SHA 1247 - Nissan Urvan Ambulance)
 * 4. SeedChoCanamanCommand (Robert Canaman & SHA 1248 - Toyota HiAce Ambulance)
 */
class SeedUatChoCommand extends Command
{
    protected $signature = 'rvms:seed-uat-cho {--fresh : Wipe previously seeded CHO UAT data before seeding}';

    protected $description = 'Seed authentic, realistic historical UAT records across all City Health Office (CHO) vehicles and drivers';

    public function handle(): int
    {
        $agency = Agency::where('code', 'CHO')->first();
        if (! $agency) {
            $this->error('CHO agency record not found!');
            return self::FAILURE;
        }

        $this->info('================================================================');
        $this->info('  Starting Master Historical UAT Seeding for City Health Office');
        $this->info('================================================================');

        // Step 1: Balloca & SHA 2625 (Service Vehicle shared base)
        $this->newLine();
        $this->info('>>> [1/4] Seeding Idelfonso Balloca & SHA 2625...');
        $exitCode = $this->call('rvms:seed-cho-balloca', [
            '--fresh' => (bool) $this->option('fresh'),
        ]);
        if ($exitCode !== self::SUCCESS) {
            $this->error('Failed seeding Balloca & SHA 2625.');
            return $exitCode;
        }

        // Step 2: Gamao & SHA 2625 (Primary Driver submissions)
        $this->newLine();
        $this->info('>>> [2/4] Seeding Rowel Gamao (Primary Driver) & SHA 2625...');
        $exitCode = $this->call('rvms:seed-cho-gamao');
        if ($exitCode !== self::SUCCESS) {
            $this->error('Failed seeding Gamao & SHA 2625.');
            return $exitCode;
        }

        // Step 3: Serrato & SHA 1247 (Nissan Urvan Ambulance)
        $this->newLine();
        $this->info('>>> [3/4] Seeding Reynaldo Serrato & SHA 1247 (Ambulance 1)...');
        $exitCode = $this->call('rvms:seed-cho-serrato');
        if ($exitCode !== self::SUCCESS) {
            $this->error('Failed seeding Serrato & SHA 1247.');
            return $exitCode;
        }

        // Step 4: Canaman & SHA 1248 (Toyota HiAce Ambulance - Web-Admin Dispatch Profile)
        $this->newLine();
        $this->info('>>> [4/4] Seeding Robert Canaman & SHA 1248 (Ambulance 2)...');
        $exitCode = $this->call('rvms:seed-cho-canaman');
        if ($exitCode !== self::SUCCESS) {
            $this->error('Failed seeding Canaman & SHA 1248.');
            return $exitCode;
        }

        $this->newLine();
        $this->info('================================================================');
        $this->info('  CHO Historical UAT Seeding Complete across all Vehicles & Drivers');
        $this->info('================================================================');

        $this->table(
            ['Agency Entity', 'Total Count for City Health Office (CHO)'],
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

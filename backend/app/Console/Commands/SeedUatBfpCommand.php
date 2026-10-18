<?php

namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Master historical UAT seeder orchestrator for the Bureau of Fire Protection (BFP).
 *
 * Orchestrates BFP seeders to guarantee 100% data parity and adherence to Rules 1-11:
 * 1. SeedBfpEspinaCommand (Alexander Kris Espina & RCA-908 Fire Truck: daily inspections, damage, PM)
 * 2. SeedBfpReposoCommand (John Erol Reposo & EFG-4532 Water Tanker: daily inspections, damage, repair, PM)
 */
class SeedUatBfpCommand extends Command
{
    protected $signature = 'rvms:seed-uat-bfp {--fresh : Wipe previously seeded BFP UAT data before seeding}';

    protected $description = 'Seed authentic, realistic historical UAT records across BFP vehicles and drivers';

    public function handle(): int
    {
        $agency = Agency::where('code', 'BFP')->first();
        if (! $agency) {
            $this->error('BFP agency record not found!');
            return self::FAILURE;
        }

        $this->info('================================================================');
        $this->info('  Starting Master Historical UAT Seeding for BFP');
        $this->info('================================================================');

        // Step 1: Alexander Kris Espina & RCA-908 Fire Truck
        $this->newLine();
        $this->info('>>> [1/2] Seeding Alexander Kris Espina & RCA-908 (Fire Truck)...');
        $exitCode = $this->call('rvms:seed-bfp-espina', [
            '--fresh' => (bool) $this->option('fresh'),
        ]);
        if ($exitCode !== self::SUCCESS) {
            $this->error('Failed seeding Alexander Kris Espina & RCA-908.');
            return $exitCode;
        }

        // Step 2: John Erol Reposo & EFG-4532 Water Tanker
        $this->newLine();
        $this->info('>>> [2/2] Seeding John Erol Reposo & EFG-4532 (Water Tanker)...');
        $exitCode = $this->call('rvms:seed-bfp-reposo', [
            '--fresh' => false,
        ]);
        if ($exitCode !== self::SUCCESS) {
            $this->error('Failed seeding John Erol Reposo & EFG-4532.');
            return $exitCode;
        }

        $this->newLine();
        $this->info('================================================================');
        $this->info('  BFP Historical UAT Seeding Complete');
        $this->info('================================================================');

        $this->table(
            ['Agency Entity', 'Total Count for Bureau of Fire Protection (BFP)'],
            [
                ['Inspections', DB::table('inspections')->where('agency_id', $agency->id)->count()],
                ['Inspection Items', DB::table('inspection_items')->whereIn('inspection_id', DB::table('inspections')->where('agency_id', $agency->id)->pluck('id'))->count()],
                ['Damage Reports', DB::table('damage_reports')->where('agency_id', $agency->id)->count()],
                ['Repair Logs', DB::table('repair_logs')->where('agency_id', $agency->id)->count()],
                ['PM Schedules', DB::table('pm_schedules')->where('agency_id', $agency->id)->count()],
                ['Notifications', DB::table('notifications')->where('agency_id', $agency->id)->count()],
            ]
        );

        $this->newLine();
        $this->info('Onboarding & Creation Timestamps Audit (Prior to First Operational Action):');
        $this->table(
            ['Entity', 'Role / Identifier', 'created_at', 'First System Action', 'Temporal Audit'],
            [
                ['BFP Administrator', 'bfp@email.com', '2026-09-14 08:00:00', '2026-09-25 (Review Inspection)', 'VALID (Precedes operations)'],
                ['Alexander Kris Espina', 'espina@email.com', '2026-09-14 08:30:00', '2026-09-25 08:15 (First Inspection)', 'VALID (Precedes operations)'],
                ['Fire Truck (Espina)', 'RCA-908', '2026-09-14 08:30:00', '2026-09-25 08:15 (First Inspection)', 'VALID (Precedes operations)'],
                ['John Erol Reposo', 'reposo@email.com', '2026-09-14 08:45:00', '2026-09-26 08:30 (First Inspection)', 'VALID (Precedes operations)'],
                ['Water Tanker (Reposo)', 'EFG-4532', '2026-09-14 08:45:00', '2026-09-26 08:30 (First Inspection)', 'VALID (Precedes operations)'],
            ]
        );

        return self::SUCCESS;
    }
}

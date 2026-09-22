<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remove every driver account and the operational test data tied to drivers,
 * so a fresh deployment can be filled with real drivers (2026-09).
 *
 * WHY THIS IS A COMMAND, NOT AN IN-APP BUTTON. The system deliberately has no
 * "delete driver" action: FR-08 keeps driver records permanently so the
 * inspections and damage reports they filed never lose their author. That rule
 * protects a LIVE system. This command is the opposite situation — a ONE-TIME
 * cleanup BEFORE go-live, clearing the seeded/sample accounts so the agencies
 * start from real data. It needs no authentication, so like `rvms:create-admin`
 * it is only reachable by someone already standing at the server.
 *
 * WHAT IT KEEPS: the four agencies, every administrator account, and (by
 * default) the vehicle records — only their driver assignments are cleared.
 * WHAT IT REMOVES: all driver accounts, and the inspections, damage reports,
 * repair logs, PM schedules, dispatches and notifications that reference them
 * (all of which are test data at this point).
 *
 * Deletes run child-before-parent through the query builder (not `truncate`, so
 * there is no MySQL-only `SET FOREIGN_KEY_CHECKS`), inside one transaction, so a
 * failure rolls the whole thing back rather than leaving the database
 * half-cleared.
 */
class ResetDrivers extends Command
{
    protected $signature = 'rvms:reset-drivers
                            {--force : Skip the confirmation prompt}
                            {--with-vehicles : Also delete the vehicle records (not just their driver links)}';

    protected $description = 'Remove all driver accounts + their test data before go-live (keeps agencies, admins, and vehicles)';

    public function handle(): int
    {
        $this->components->info('RVMS — reset drivers (pre-go-live cleanup)');

        $driverCount = DB::table('users')->where('role', 'driver')->count();

        if ($driverCount === 0 && ! $this->option('with-vehicles')) {
            $this->components->info('No driver accounts to remove. Nothing to do.');

            return self::SUCCESS;
        }

        // Show exactly what will go, so an accidental run is caught before it commits.
        $this->newLine();
        $this->line('  This will PERMANENTLY delete:');
        $this->line("    • {$driverCount} driver account(s)");
        foreach (['inspections', 'inspection_items', 'damage_reports', 'repair_logs', 'pm_schedules', 'dispatches', 'notifications'] as $table) {
            $this->line(sprintf('    • %d row(s) in %s', DB::table($table)->count(), $table));
        }
        if ($this->option('with-vehicles')) {
            $this->line(sprintf('    • %d vehicle record(s)', DB::table('vehicles')->count()));
        }
        $this->newLine();
        $this->line('  This will KEEP: the agencies, every administrator account'
            .($this->option('with-vehicles') ? '.' : ', and the vehicle records (driver links cleared).'));
        $this->newLine();
        $this->components->warn('This cannot be undone. Back up the database first (mysqldump).');

        if (! $this->option('force') && ! $this->confirm('Proceed with the cleanup?', false)) {
            $this->components->info('Cancelled. Nothing was changed.');

            return self::SUCCESS;
        }

        $deletedDrivers = DB::transaction(function () {
            // Children before parents, so foreign keys never block a delete.
            DB::table('inspection_items')->delete();
            DB::table('inspections')->delete();
            DB::table('damage_reports')->delete();
            DB::table('repair_logs')->delete();
            DB::table('pm_schedules')->delete();
            DB::table('dispatches')->delete();
            DB::table('notifications')->delete();

            if ($this->option('with-vehicles')) {
                DB::table('vehicles')->delete();
            } else {
                // Release the kept vehicles from the drivers about to be removed.
                DB::table('vehicles')->update([
                    'assigned_driver_id' => null,
                    'secondary_driver_id' => null,
                ]);
            }

            return DB::table('users')->where('role', 'driver')->delete();
        });

        $this->newLine();
        $this->components->info("Done. Removed {$deletedDrivers} driver account(s) and their test data.");
        $this->line('  Add real drivers now: the admin can add them on the Drivers page, or');
        $this->line('  drivers self-register on the mobile app and an admin approves them.');

        return self::SUCCESS;
    }
}

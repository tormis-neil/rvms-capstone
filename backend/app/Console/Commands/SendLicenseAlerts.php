<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MaintenanceAlerts;
use Illuminate\Console\Command;

/**
 * The daily sweep for driver licences approaching expiry or already expired
 * (FR-08 → FR-21), against each agency's own configurable warning window.
 *
 * This exists because the alert is TIME-driven: a licence expires when a date
 * arrives, with no user action to react to. The driver controllers raise the
 * same alert immediately for a licence an admin has just recorded, so this
 * command is the safety net for time passing rather than the only path.
 *
 * The per-driver idempotency lives in MaintenanceAlerts, so running daily does
 * not produce one identical row per morning.
 */
class SendLicenseAlerts extends Command
{
    protected $signature = 'rvms:license-alerts';

    protected $description = 'Notify administrators of expiring and expired driver licences';

    public function handle(MaintenanceAlerts $alerts): int
    {
        $raised = 0;

        User::query()
            ->where('role', User::ROLE_DRIVER)
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('license_expiry_date')
            ->with('agency')
            ->chunkById(200, function ($drivers) use ($alerts, &$raised) {
                foreach ($drivers as $driver) {
                    if ($alerts->raiseForDriver($driver)) {
                        $raised++;
                    }
                }
            });

        $this->info("Licence alerts raised for {$raised} driver(s).");

        return self::SUCCESS;
    }
}

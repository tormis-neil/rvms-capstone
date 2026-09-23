<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MaintenanceAlerts;
use Illuminate\Console\Command;

/**
 * The daily sweep for driver credentials approaching expiry or already expired
 * (FR-08 → FR-21), against each agency's own configurable warning window — both
 * the professional licence and the TESDA NC II (Driving), monitored alike.
 *
 * This exists because the alert is TIME-driven: a credential expires when a date
 * arrives, with no user action to react to. The driver controllers raise the
 * same alerts immediately for a credential an admin has just recorded, so this
 * command is the safety net for time passing rather than the only path.
 *
 * The per-driver, per-credential idempotency lives in MaintenanceAlerts, so
 * running daily does not produce one identical row per morning.
 */
class SendLicenseAlerts extends Command
{
    protected $signature = 'rvms:license-alerts';

    protected $description = 'Notify administrators of expiring and expired driver licences and NC II certificates';

    public function handle(MaintenanceAlerts $alerts): int
    {
        $licences = 0;
        $ncIi = 0;

        User::query()
            ->where('role', User::ROLE_DRIVER)
            ->where('status', User::STATUS_ACTIVE)
            // Either credential can be due; each raise method no-ops when its
            // own date is null, so sweeping the union covers both cleanly.
            ->where(fn ($q) => $q
                ->whereNotNull('license_expiry_date')
                ->orWhereNotNull('nc_ii_expiry_date'))
            ->with('agency')
            ->chunkById(200, function ($drivers) use ($alerts, &$licences, &$ncIi) {
                foreach ($drivers as $driver) {
                    if ($alerts->raiseForDriver($driver)) {
                        $licences++;
                    }
                    if ($alerts->raiseNcIiForDriver($driver)) {
                        $ncIi++;
                    }
                }
            });

        $this->info("Licence alerts raised for {$licences} driver(s); NC II alerts for {$ncIi} driver(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\PmSchedule;
use App\Services\MaintenanceAlerts;
use Illuminate\Console\Command;

/**
 * The daily sweep for preventive-maintenance schedules that have reached Due
 * Soon or Due (FR-14 → FR-21).
 *
 * This exists because the alert is TIME-driven: a schedule becomes due when a
 * date arrives or mileage is updated elsewhere, with no user action to react to.
 * The PM controllers raise the same alert immediately for a schedule an admin
 * has just written, so this command is the safety net for time passing rather
 * than the only path.
 *
 * Runs right after rvms:recalculate-pm, so it announces today's statuses rather
 * than yesterday's. The per-schedule idempotency lives in MaintenanceAlerts, so
 * a re-run does not re-alert.
 */
class SendPmAlerts extends Command
{
    protected $signature = 'rvms:pm-alerts';

    protected $description = 'Notify administrators and drivers of Due Soon / Due preventive maintenance';

    public function handle(MaintenanceAlerts $alerts): int
    {
        $raised = 0;

        PmSchedule::query()
            ->withoutGlobalScopes() // system job: sweep every agency
            ->whereIn('status', [PmSchedule::STATUS_DUE_SOON, PmSchedule::STATUS_DUE])
            ->with(['vehicle.assignedDriver'])
            ->chunkById(200, function ($schedules) use ($alerts, &$raised) {
                foreach ($schedules as $schedule) {
                    if ($alerts->raiseForSchedule($schedule)) {
                        $raised++;
                    }
                }
            });

        $this->info("PM alerts raised for {$raised} schedule(s).");

        return self::SUCCESS;
    }
}

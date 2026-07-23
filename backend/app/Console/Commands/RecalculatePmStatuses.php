<?php

namespace App\Console\Commands;

use App\Models\PmSchedule;
use Illuminate\Console\Command;

/**
 * Recomputes the Upcoming / Due Soon / Due status of every active PM schedule
 * from the vehicle's current mileage (mileage-based) or today's date
 * (time-based) against the configurable Due-Soon thresholds (FR-14, plan §6.7).
 * Completed schedules are left untouched. Runs daily via the scheduler.
 */
class RecalculatePmStatuses extends Command
{
    protected $signature = 'rvms:recalculate-pm';

    protected $description = 'Recompute Upcoming/Due Soon/Due for active PM schedules';

    public function handle(): int
    {
        $updated = 0;

        PmSchedule::query()
            ->withoutGlobalScopes() // system job: sweep every agency
            ->where('status', '!=', PmSchedule::STATUS_COMPLETED)
            ->with('vehicle')
            ->chunkById(200, function ($schedules) use (&$updated) {
                foreach ($schedules as $schedule) {
                    $new = $schedule->recalculatedStatus();
                    if ($new !== $schedule->status) {
                        $schedule->update(['status' => $new]);
                        $updated++;
                    }
                }
            });

        $this->info("PM statuses recalculated. {$updated} schedule(s) changed.");

        return self::SUCCESS;
    }
}

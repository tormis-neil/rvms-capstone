<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Unify all existing PM schedules under the dual-trigger ("Mileage & Time-Based" —
     * whichever comes first) model so every existing record on Railway carries both
     * a mileage target and a calendar target without wiping any data.
     */
    public function up(): void
    {
        $schedules = DB::table('pm_schedules')->get();

        foreach ($schedules as $schedule) {
            $vehicleMileage = (int) (DB::table('vehicles')
                ->where('id', $schedule->vehicle_id)
                ->value('current_mileage') ?? 0);

            $updates = [
                'pm_type' => 'Mileage & Time-Based',
            ];

            if ($schedule->due_mileage === null) {
                $interval = (int) ($schedule->interval_km ?: 5000);
                $lastPm = $schedule->last_pm_mileage !== null
                    ? (int) $schedule->last_pm_mileage
                    : max(0, $vehicleMileage - 2000);
                $updates['interval_km'] = $interval;
                $updates['last_pm_mileage'] = $lastPm;
                $updates['due_mileage'] = $lastPm + $interval;
                $updates['due_soon_threshold_km'] = (int) ($schedule->due_soon_threshold_km ?: 500);
            }

            if ($schedule->due_date === null) {
                $baseDate = $schedule->created_at ? Carbon::parse($schedule->created_at) : Carbon::now();
                // Keep active mileage-driven schedules on their intended status by setting
                // the companion calendar target 6 months out from creation (or at least 30 days ahead).
                $candidateDate = $baseDate->copy()->addMonths(6);
                if ($schedule->status !== 'Completed' && $candidateDate->lessThanOrEqualTo(Carbon::today()->addDays(20))) {
                    $candidateDate = Carbon::today()->addMonths(3);
                }
                $updates['due_date'] = $candidateDate->toDateString();
                $updates['due_soon_threshold_days'] = (int) ($schedule->due_soon_threshold_days ?: 14);
            }

            DB::table('pm_schedules')
                ->where('id', $schedule->id)
                ->update($updates);
        }
    }

    public function down(): void
    {
        // Non-destructive data backfill; nothing to revert.
    }
};


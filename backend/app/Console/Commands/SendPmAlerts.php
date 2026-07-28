<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\PmSchedule;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;

/**
 * Alerts on preventive-maintenance schedules that have reached Due Soon or Due
 * (FR-14 → FR-21).
 *
 * Two audiences, as FR-21 splits them: administrators get the Due Soon / Due
 * alert so they can arrange the service, and the vehicle's assigned driver gets
 * a preventive-maintenance reminder.
 *
 * Runs daily right after rvms:recalculate-pm, so it must not re-alert a
 * schedule that sits at Due Soon for a fortnight. One alert per schedule per
 * state; crossing from Due Soon to Due raises a fresh one.
 */
class SendPmAlerts extends Command
{
    protected $signature = 'rvms:pm-alerts';

    protected $description = 'Notify administrators and drivers of Due Soon / Due preventive maintenance';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $raised = 0;

        PmSchedule::query()
            ->withoutGlobalScopes() // system job: sweep every agency
            ->whereIn('status', [PmSchedule::STATUS_DUE_SOON, PmSchedule::STATUS_DUE])
            ->with(['vehicle.assignedDriver'])
            ->chunkById(200, function ($schedules) use ($dispatcher, &$raised) {
                foreach ($schedules as $schedule) {
                    $adminType = $schedule->status === PmSchedule::STATUS_DUE
                        ? Notification::TYPE_PM_DUE
                        : Notification::TYPE_PM_DUE_SOON;

                    if ($this->alreadyRaised($schedule, $adminType)) {
                        continue;
                    }

                    $plate = $schedule->vehicle?->plate_number ?? 'A vehicle';
                    $message = sprintf('%s — %s is %s.', $plate, $schedule->service_target, strtolower($schedule->status));
                    $payload = [
                        'pm_schedule_id' => $schedule->id,
                        'vehicle_id' => $schedule->vehicle_id,
                        'plate' => $schedule->vehicle?->plate_number,
                        'service_target' => $schedule->service_target,
                        'state' => $schedule->status,
                    ];

                    $dispatcher->sendToMany(
                        $dispatcher->adminsOf($schedule->agency_id),
                        $adminType,
                        $schedule->status === PmSchedule::STATUS_DUE ? 'PM Due' : 'PM Due Soon',
                        $message,
                        $payload,
                    );

                    // The driver gets the reminder FR-21 names for them.
                    $driver = $schedule->vehicle?->assignedDriver;
                    if ($driver && $driver->status === \App\Models\User::STATUS_ACTIVE) {
                        $dispatcher->send(
                            $driver,
                            Notification::TYPE_PM_REMINDER,
                            'Preventive Maintenance',
                            $message,
                            $payload,
                        );
                    }

                    $raised++;
                }
            });

        $this->info("PM alerts raised for {$raised} schedule(s).");

        return self::SUCCESS;
    }

    /**
     * Already alerted for this schedule in this state. Keyed on the schedule id
     * and the state, so Due Soon → Due raises a new alert while a schedule
     * sitting at Due Soon stays quiet.
     */
    private function alreadyRaised(PmSchedule $schedule, string $type): bool
    {
        return Notification::query()
            ->withoutGlobalScopes()
            ->where('type', $type)
            ->where('data->pm_schedule_id', $schedule->id)
            ->exists();
    }
}

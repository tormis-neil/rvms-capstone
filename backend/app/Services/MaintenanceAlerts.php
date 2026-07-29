<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\User;

/**
 * Raises the two TIME-driven alerts FR-21 defines: preventive maintenance
 * Due Soon / Due (FR-14 → FR-21) and driver licences approaching or past expiry
 * (FR-08 → FR-21).
 *
 * They are time-driven rather than event-driven — a licence expires because a
 * date arrived, not because anybody clicked anything — so a scheduled command
 * has to sweep for them daily. But the daily sweep alone leaves a hole: an admin
 * who CREATES a schedule that is already due, or records a licence that is
 * already inside the warning window, would wait up to a day to be told about
 * something they just entered.
 *
 * So both paths call the same methods here:
 *   - rvms:pm-alerts / rvms:license-alerts sweep everything, daily;
 *   - the PM and driver controllers raise for the single record they just wrote.
 *
 * The idempotency rules live here rather than in the callers, so the immediate
 * path and the daily path can never disagree about what counts as "already
 * alerted" and produce a duplicate between them.
 */
class MaintenanceAlerts
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * Alert on one PM schedule if it is Due Soon or Due and has not already been
     * alerted in that state.
     *
     * @return bool whether an alert was raised
     */
    public function raiseForSchedule(PmSchedule $schedule): bool
    {
        if (! in_array($schedule->status, [PmSchedule::STATUS_DUE_SOON, PmSchedule::STATUS_DUE], true)) {
            return false;
        }

        $adminType = $schedule->status === PmSchedule::STATUS_DUE
            ? Notification::TYPE_PM_DUE
            : Notification::TYPE_PM_DUE_SOON;

        // Keyed on the schedule AND the state, so Due Soon -> Due escalates while
        // a schedule sitting at Due Soon for a fortnight stays quiet.
        $alreadyRaised = Notification::query()
            ->withoutGlobalScopes()
            ->where('type', $adminType)
            ->where('data->pm_schedule_id', $schedule->id)
            ->exists();

        if ($alreadyRaised) {
            return false;
        }

        $schedule->loadMissing('vehicle.assignedDriver');

        $plate = $schedule->vehicle?->plate_number ?? 'A vehicle';
        $message = sprintf('%s — %s is %s.', $plate, $schedule->service_target, strtolower($schedule->status));
        $payload = [
            'pm_schedule_id' => $schedule->id,
            'vehicle_id' => $schedule->vehicle_id,
            'plate' => $schedule->vehicle?->plate_number,
            'service_target' => $schedule->service_target,
            'state' => $schedule->status,
        ];

        $this->dispatcher->sendToMany(
            $this->dispatcher->adminsOf($schedule->agency_id),
            $adminType,
            $schedule->status === PmSchedule::STATUS_DUE ? 'PM Due' : 'PM Due Soon',
            $message,
            $payload,
        );

        // The driver gets the reminder FR-21 names for them.
        $driver = $schedule->vehicle?->assignedDriver;
        if ($driver && $driver->status === User::STATUS_ACTIVE) {
            $this->dispatcher->send(
                $driver,
                Notification::TYPE_PM_REMINDER,
                'Preventive Maintenance',
                $message,
                $payload,
            );
        }

        return true;
    }

    /**
     * Alert on one driver's licence if it is Expiring Soon or Expired and has
     * not already been alerted for THAT expiry date.
     *
     * @return bool whether an alert was raised
     */
    public function raiseForDriver(User $driver): bool
    {
        if ($driver->role !== User::ROLE_DRIVER || $driver->status !== User::STATUS_ACTIVE) {
            return false;
        }

        if ($driver->license_expiry_date === null) {
            return false;
        }

        $state = $driver->licenseStatus();

        if (! in_array($state, ['Expiring Soon', 'Expired'], true)) {
            return false;
        }

        $type = $state === 'Expired'
            ? Notification::TYPE_LICENSE_EXPIRED
            : Notification::TYPE_LICENSE_EXPIRING;

        // Keyed on the driver AND the current expiry date, so a renewal starts a
        // fresh cycle instead of being silenced forever by the old licence's alert.
        $alreadyRaised = Notification::query()
            ->withoutGlobalScopes()
            ->where('type', $type)
            ->where('data->driver_id', $driver->id)
            ->where('data->expires_on', $driver->license_expiry_date->toDateString())
            ->exists();

        if ($alreadyRaised) {
            return false;
        }

        $message = $state === 'Expired'
            ? sprintf('%s — licence expired on %s.', $driver->name, $driver->license_expiry_date->format('M j, Y'))
            : sprintf('%s — licence expires %s.', $driver->name, $driver->license_expiry_date->format('M j, Y'));

        $this->dispatcher->sendToMany(
            $this->dispatcher->adminsOf($driver->agency_id),
            $type,
            $state === 'Expired' ? 'License Expired' : 'License Expiring Soon',
            $message,
            [
                'driver_id' => $driver->id,
                'driver' => $driver->name,
                'expires_on' => $driver->license_expiry_date->toDateString(),
            ],
        );

        return true;
    }
}

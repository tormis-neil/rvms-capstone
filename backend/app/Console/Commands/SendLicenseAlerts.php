<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;

/**
 * Alerts every agency's administrators about driver licences that are
 * approaching expiry or have already expired (FR-08 → FR-21).
 *
 * The "approaching" window is each agency's own configurable
 * license_expiry_warning_days, never a constant.
 *
 * Runs daily, so it must not re-alert the same licence every morning: a driver
 * whose licence expires in three weeks would otherwise generate twenty-one
 * identical rows per admin. One alert per driver per state is raised, and a new
 * one only when the state actually changes (Expiring Soon → Expired) or when
 * the licence is renewed and later approaches expiry again.
 */
class SendLicenseAlerts extends Command
{
    protected $signature = 'rvms:license-alerts';

    protected $description = 'Notify administrators of expiring and expired driver licences';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $raised = 0;

        User::query()
            ->where('role', User::ROLE_DRIVER)
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('license_expiry_date')
            ->with('agency')
            ->chunkById(200, function ($drivers) use ($dispatcher, &$raised) {
                foreach ($drivers as $driver) {
                    $state = $driver->licenseStatus();

                    if (! in_array($state, ['Expiring Soon', 'Expired'], true)) {
                        continue;
                    }

                    $type = $state === 'Expired'
                        ? Notification::TYPE_LICENSE_EXPIRED
                        : Notification::TYPE_LICENSE_EXPIRING;

                    if ($this->alreadyRaised($driver, $type)) {
                        continue;
                    }

                    $message = $state === 'Expired'
                        ? sprintf('%s — licence expired on %s.', $driver->name, $driver->license_expiry_date->format('M j, Y'))
                        : sprintf('%s — licence expires %s.', $driver->name, $driver->license_expiry_date->format('M j, Y'));

                    $dispatcher->sendToMany(
                        $dispatcher->adminsOf($driver->agency_id),
                        $type,
                        $state === 'Expired' ? 'License Expired' : 'License Expiring Soon',
                        $message,
                        [
                            'driver_id' => $driver->id,
                            'driver' => $driver->name,
                            'expires_on' => $driver->license_expiry_date->toDateString(),
                        ],
                    );

                    $raised++;
                }
            });

        $this->info("Licence alerts raised for {$raised} driver(s).");

        return self::SUCCESS;
    }

    /**
     * Already alerted for this driver in this state, against the CURRENT expiry
     * date. Keying on the date means a renewal starts a fresh cycle rather than
     * being silenced forever by an alert about the old licence.
     */
    private function alreadyRaised(User $driver, string $type): bool
    {
        return Notification::query()
            ->withoutGlobalScopes() // system job: sweep every agency
            ->where('type', $type)
            ->where('data->driver_id', $driver->id)
            ->where('data->expires_on', $driver->license_expiry_date->toDateString())
            ->exists();
    }
}

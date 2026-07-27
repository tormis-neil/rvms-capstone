<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

/**
 * The single gate for every vehicle status write (FR-18, design decision 9).
 *
 * FR-18 says the shared status may be "updated from any applicable module", so
 * every module keeps its own status control. What this class adds is the one
 * rule that cannot be left to the UI:
 *
 *   A vehicle on an ACTIVE DISPATCH keeps its Dispatched status until the
 *   dispatch is closed, where the return status is set (FR-15 → FR-16).
 *
 * Any other module attempting a write while a dispatch is open is refused with
 * a message naming the mission, so the admin is sent to the right screen
 * instead of silently breaking the single-status guarantee.
 *
 * Every accepted write also stamps WHERE it came from, so the confirmation
 * dialog can tell the next admin what they are about to override.
 */
class VehicleStatusWriter
{
    /** Sources, used verbatim in the "set <when> · <source>" line. */
    public const SOURCE_VEHICLES = 'Vehicle Management';

    public const SOURCE_INSPECTION = 'Inspection Review';

    public const SOURCE_DAMAGE = 'Damage Review';

    public const SOURCE_REPAIR = 'Repair Logs';

    public const SOURCE_PM = 'PM Schedules';

    public const SOURCE_DISPATCH_OPEN = 'Dispatch Opened';

    public const SOURCE_DISPATCH_CLOSE = 'Dispatch Closed';

    /**
     * Write a status from a module other than Dispatch.
     *
     * @param  array<string, mixed>  $extra  additional vehicle attributes. Pass
     *                                       'remarks' only when the caller's form
     *                                       actually carries the field — an empty
     *                                       value legitimately clears the note,
     *                                       so it must not be confused with
     *                                       "remarks were not submitted".
     *
     * @throws ValidationException when the vehicle is on an active dispatch
     */
    public function write(Vehicle $vehicle, string $status, string $source, array $extra = []): void
    {
        $this->assertNotOnActiveDispatch($vehicle);

        $vehicle->update(array_merge($extra, [
            'status' => $status,
            'status_source' => $source,
            'status_changed_at' => now(),
        ]));
    }

    /**
     * Write a status from the Dispatch module itself, which owns the Dispatched
     * state and is therefore exempt from the guard. Also used on close, where
     * the mileage-on-arrival may accompany the return status (FR-16 → FR-14).
     *
     * @param  array<string, mixed>  $extra  additional vehicle attributes
     */
    public function writeFromDispatch(Vehicle $vehicle, string $status, string $source, array $extra = []): void
    {
        $vehicle->update(array_merge($extra, [
            'status' => $status,
            'status_source' => $source,
            'status_changed_at' => now(),
        ]));
    }

    /** The open dispatch holding this vehicle, if any. */
    public function activeDispatch(Vehicle $vehicle): ?Dispatch
    {
        return Dispatch::query()
            ->withoutGlobalScopes() // the guard must hold regardless of caller scope
            ->where('vehicle_id', $vehicle->id)
            ->whereNull('time_in')
            ->latest('time_out')
            ->first();
    }

    /**
     * @throws ValidationException
     */
    private function assertNotOnActiveDispatch(Vehicle $vehicle): void
    {
        $dispatch = $this->activeDispatch($vehicle);

        if ($dispatch === null) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => sprintf(
                '%s is on an active dispatch (%s — %s, out since %s). '
                .'Close the dispatch to set its return status.',
                $vehicle->plate_number,
                $dispatch->missionLabel(),
                $dispatch->location,
                $dispatch->time_out?->format('M j, g:i A') ?? 'earlier',
            ),
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\Vehicle;

/**
 * Moves the Dispatched status when an OPEN dispatch is edited onto a different
 * vehicle (FR-15, FR-16, FR-18 — 2026-08, lead-reported).
 *
 * Opening and closing a dispatch always wrote the vehicle's status; editing one
 * never did. `update()` simply saved the row, and because the edit form carries
 * `vehicle_id`, correcting "I dispatched the wrong truck" left the fleet in two
 * wrong states at once:
 *
 *   - the NEW vehicle held an active dispatch while still reading Operational,
 *     so the Vehicles page, the dashboard, the availability list and the
 *     vehicle-status report all reported a truck as available while it was out
 *     in the field — four screens contradicting FR-18 simultaneously;
 *   - the OLD vehicle stayed Dispatched with nothing pointing at it, so no
 *     close would ever release it. Being not-Operational it also dropped out of
 *     the dispatch dropdown, stranding it until an admin happened to notice and
 *     reset it by hand from Vehicles.
 *
 * Lives in a service because both the web controller and the API controller
 * have to do this identically; a copy in each would be a drift waiting to
 * happen, which is the same reasoning that put the uniqueness rule in
 * DispatchGuard.
 */
class DispatchReassignment
{
    public function __construct(private readonly VehicleStatusWriter $status) {}

    /**
     * Hand the Dispatched status from the previous vehicle to the current one.
     *
     * Call inside the same transaction as the update, AFTER the dispatch row is
     * saved — the new vehicle must already own the dispatch before it is marked
     * Dispatched, so the two never disagree even for an instant.
     *
     * Does nothing when the vehicle did not change, and nothing at all for a
     * CLOSED dispatch: that vehicle already received its return status on close
     * (FR-16), and re-marking it Dispatched because someone corrected a
     * historical record would put a parked vehicle back out on a mission.
     */
    public function handOver(Dispatch $dispatch, int $previousVehicleId): void
    {
        if (! $dispatch->isActive() || $dispatch->vehicle_id === $previousVehicleId) {
            return;
        }

        $this->release($previousVehicleId);

        $this->status->writeFromDispatch(
            $dispatch->vehicle,
            Vehicle::STATUS_DISPATCHED,
            VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
        );
    }

    /**
     * Put the vehicle this dispatch used to name back into service.
     *
     * Operational is the right release value rather than a guess: only
     * Operational vehicles can be dispatched in the first place, so it is what
     * the vehicle read before this dispatch claimed it. (No previous-status
     * column exists to restore from — `status_source`/`status_changed_at`
     * record where a status came from, not what preceded it.)
     *
     * Guarded on the current value so an edit can never invent a status change:
     * if the vehicle is not Dispatched, something else already owns its state
     * and this is not the module to overrule it.
     */
    private function release(int $vehicleId): void
    {
        $previous = Vehicle::query()->find($vehicleId);

        if ($previous === null || $previous->status !== Vehicle::STATUS_DISPATCHED) {
            return;
        }

        $this->status->writeFromDispatch(
            $previous,
            Vehicle::STATUS_OPERATIONAL,
            VehicleStatusWriter::SOURCE_DISPATCH_EDIT,
        );
    }
}

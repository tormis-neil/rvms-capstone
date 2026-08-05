<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deleting and restoring vehicles and drivers (FR-05, FR-06 — extended 2026-08).
 *
 * One class because deletion is never just `->delete()`. Each kind of record is
 * attached to live state that has to be released first, and to history that must
 * NOT be — the whole reason these are soft deletes. Putting the rules here keeps
 * the web and API controllers identical, exactly as DispatchGuard does for
 * "one mission at a time".
 *
 * The refusals are deliberately narrow. A vehicle with fifty inspections deletes
 * happily — that history is the point, and it survives untouched. What is
 * refused is deleting something the agency is USING RIGHT NOW: a vehicle or a
 * driver out on an active dispatch. Removing either mid-mission would leave a
 * dispatch that cannot be closed, and closing is what writes the return status
 * (FR-16), so the vehicle would be stranded reading "Dispatched" forever.
 */
class RecordDeletion
{
    public function __construct(private readonly DispatchGuard $dispatches) {}

    /**
     * Soft-delete a vehicle.
     *
     * @throws ValidationException when it is out on an active dispatch
     */
    public function deleteVehicle(Vehicle $vehicle): void
    {
        if ($clash = $this->dispatches->openForVehicle($vehicle->id)) {
            throw ValidationException::withMessages([
                'vehicle' => sprintf(
                    '%s cannot be deleted while it is out on an active dispatch (%s — %s). '
                    .'Close the dispatch in Dispatch Logs first.',
                    $vehicle->plate_number,
                    $clash->missionLabel(),
                    $clash->location,
                ),
            ]);
        }

        $vehicle->delete();
    }

    /**
     * Soft-delete a driver, releasing whatever they still hold.
     *
     * A driver may be the primary driver of several vehicles (Ch4 ERD). Those
     * assignments are cleared rather than left pointing at someone who is gone,
     * so the Vehicles page shows the truth — "Unassigned" — and an admin can
     * hand the vehicle to somebody else. The driver's inspections and damage
     * reports keep their `driver_id` and still read back their name, because
     * every one of those relations is `withTrashed()`.
     *
     * @throws ValidationException when they are out on an active dispatch
     */
    public function deleteDriver(User $driver): void
    {
        if ($clash = $this->dispatches->openForDriver($driver->id)) {
            throw ValidationException::withMessages([
                'driver' => sprintf(
                    '%s cannot be deleted while they are out on an active dispatch (%s — %s). '
                    .'Close the dispatch in Dispatch Logs first.',
                    $driver->name,
                    $clash->missionLabel(),
                    $clash->location,
                ),
            ]);
        }

        DB::transaction(function () use ($driver) {
            Vehicle::query()
                ->where('assigned_driver_id', $driver->id)
                ->update(['assigned_driver_id' => null]);

            // A deleted account must not keep a working session. The model's
            // soft delete already hides it from the auth provider, so a bearer
            // token resolves to nothing — revoking is belt and braces, and it
            // also stops the phone from being pushed to.
            $driver->tokens()->delete();
            $driver->forceFill(['fcm_token' => null])->saveQuietly();

            $driver->delete();
        });
    }

    /**
     * Restore a deleted vehicle.
     *
     * The plate is unique per agency and a deleted row still occupies that
     * index, so restoring is the ONLY way to get a plate back — which is the
     * behaviour we want. Re-adding it by hand would create a second record and
     * split the vehicle's history in two.
     */
    public function restoreVehicle(Vehicle $vehicle): void
    {
        $vehicle->restore();
    }

    /**
     * Restore a deleted driver.
     *
     * Their vehicle assignments are NOT restored: another driver may have been
     * given that vehicle in the meantime, and each vehicle has at most one
     * primary driver. The admin reassigns deliberately.
     */
    public function restoreDriver(User $driver): void
    {
        $driver->restore();
    }
}

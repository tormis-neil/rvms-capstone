<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

/**
 * "One mission at a time" for both the vehicle and the driver (FR-15, FR-18).
 *
 * Lives in one class because the rule has to hold in two places:
 *
 *  - StoreDispatchRequest calls the lookups to produce a friendly 422 naming the
 *    mission that is holding the vehicle or driver.
 *  - The controllers call assertFree() again INSIDE their transaction, with the
 *    vehicle and driver rows locked, because validation alone has a gap: two
 *    admins submitting in the same instant can both pass the check before either
 *    has inserted. The lock serialises them so the second one loses.
 *
 * Both paths share the same messages, so the admin sees identical wording
 * whichever layer catches it.
 *
 * Note: lockForUpdate is a no-op on SQLite (the test database) and enforced on
 * MySQL, which is what the agencies run. The validation layer is what the tests
 * exercise; the lock is the production safety net underneath it.
 */
class DispatchGuard
{
    /** The open dispatch holding this vehicle, if any. */
    public function openForVehicle(int $vehicleId, ?int $ignoreDispatchId = null): ?Dispatch
    {
        return $this->openQuery($ignoreDispatchId)
            ->where('vehicle_id', $vehicleId)
            ->first();
    }

    /**
     * The open dispatch holding this driver, if any — as EITHER the primary or
     * the second driver (2026-09). A two-person crew means both drivers are out
     * with the vehicle, so either role makes the driver unavailable for another
     * active dispatch.
     */
    public function openForDriver(int $driverId, ?int $ignoreDispatchId = null): ?Dispatch
    {
        return $this->openQuery($ignoreDispatchId)
            ->where(fn ($q) => $q
                ->where('driver_id', $driverId)
                ->orWhere('second_driver_id', $driverId))
            ->first();
    }

    public function vehicleMessage(Dispatch $clash): string
    {
        return sprintf(
            '%s is already out on an active dispatch (%s — %s, out since %s). '
            .'Close that dispatch before sending it out again.',
            $clash->vehicle?->plate_number ?? 'That vehicle',
            $clash->missionLabel(),
            $clash->location,
            $clash->time_out?->format('M j, g:i A') ?? 'earlier',
        );
    }

    /**
     * @param  string|null  $driverName  The busy driver's name — pass it when the
     *   clash is on the SECOND driver, since `$clash->driver` names the clashing
     *   dispatch's PRIMARY driver, who may be someone else.
     */
    public function driverMessage(Dispatch $clash, ?string $driverName = null): string
    {
        return sprintf(
            '%s is already out on an active dispatch (%s — %s, driving %s since %s). '
            .'Close that dispatch or choose another driver.',
            $driverName ?? $clash->driver?->name ?? 'That driver',
            $clash->missionLabel(),
            $clash->location,
            $clash->vehicle?->plate_number ?? 'another vehicle',
            $clash->time_out?->format('M j, g:i A') ?? 'earlier',
        );
    }

    /**
     * Re-check with the vehicle and driver rows locked. Call inside the same
     * transaction that creates the dispatch.
     *
     * @throws ValidationException
     */
    public function assertFree(int $vehicleId, int $driverId, ?int $secondDriverId = null, ?int $ignoreDispatchId = null): void
    {
        // Locking the vehicle and driver rows is what serialises two concurrent
        // opens: the second transaction waits here until the first has committed,
        // and then sees the dispatch it just inserted. The second driver is
        // locked too, so a crew member cannot be claimed by two opens at once.
        Vehicle::withoutGlobalScopes()->whereKey($vehicleId)->lockForUpdate()->first();
        User::query()->whereKey($driverId)->lockForUpdate()->first();
        if ($secondDriverId) {
            User::query()->whereKey($secondDriverId)->lockForUpdate()->first();
        }

        if ($clash = $this->openForVehicle($vehicleId, $ignoreDispatchId)) {
            throw ValidationException::withMessages(['vehicle_id' => $this->vehicleMessage($clash)]);
        }

        if ($clash = $this->openForDriver($driverId, $ignoreDispatchId)) {
            throw ValidationException::withMessages(['driver_id' => $this->driverMessage($clash)]);
        }

        if ($secondDriverId && $clash = $this->openForDriver($secondDriverId, $ignoreDispatchId)) {
            throw ValidationException::withMessages([
                'second_driver_id' => $this->driverMessage($clash, User::query()->whereKey($secondDriverId)->value('name')),
            ]);
        }
    }

    /** Open dispatches, unscoped so the rule holds regardless of the caller. */
    private function openQuery(?int $ignoreDispatchId)
    {
        return Dispatch::query()
            ->withoutGlobalScopes()
            ->whereNull('time_in')
            ->when($ignoreDispatchId, fn ($q) => $q->whereKeyNot($ignoreDispatchId))
            ->latest('time_out');
    }
}

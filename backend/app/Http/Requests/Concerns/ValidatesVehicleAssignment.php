<?php

namespace App\Http\Requests\Concerns;

use App\Models\Vehicle;
use Illuminate\Contracts\Validation\Validator;

/**
 * Shared rule for the Drivers page "Assign Vehicle" + "Assign as" role
 * (Primary / Secondary), 2026-09.
 *
 * The Vehicles page manages a vehicle's two drivers directly; this is the
 * mirror on the Drivers page, so an admin can put a driver into either slot of
 * a vehicle from the person's own form. Both the Store and Update driver
 * requests share the check so the two can never word or enforce it differently.
 *
 * $driverId is null on create (a new driver holds no slot yet) and the edited
 * driver's id on update (so re-saving the same assignment is not a self-clash).
 */
trait ValidatesVehicleAssignment
{
    protected function validateVehicleAssignment(Validator $validator, ?int $driverId): void
    {
        $vehicleId = (int) $this->input('assigned_vehicle_id');
        if (! $vehicleId) {
            return;
        }

        $vehicle = Vehicle::withoutGlobalScopes()->find($vehicleId);
        if (! $vehicle) {
            return; // the exists rule already reported it
        }

        $role = $this->input('assigned_vehicle_role', 'primary');

        if ($role === 'secondary') {
            if ((int) $vehicle->assigned_driver_id === $driverId && $driverId !== null) {
                $validator->errors()->add('assigned_vehicle_id',
                    'This driver is already the primary driver of that vehicle — they cannot also be its secondary.');
            }
            if ($vehicle->secondary_driver_id && (int) $vehicle->secondary_driver_id !== $driverId) {
                $validator->errors()->add('assigned_vehicle_id',
                    'That vehicle already has a secondary driver. Reassign it from the Vehicles page first.');
            }

            return;
        }

        // primary
        if ((int) $vehicle->secondary_driver_id === $driverId && $driverId !== null) {
            $validator->errors()->add('assigned_vehicle_id',
                'This driver is already the secondary driver of that vehicle — they cannot also be its primary.');
        }
        if ($vehicle->assigned_driver_id && (int) $vehicle->assigned_driver_id !== $driverId) {
            $validator->errors()->add('assigned_vehicle_id',
                'That vehicle is not available to assign as primary (it may already have a driver).');
        }
    }
}

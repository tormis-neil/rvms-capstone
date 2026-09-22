<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Vehicle
 */
class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'type' => $this->type,
            'plate_number' => $this->plate_number,
            'make' => $this->make,
            'model' => $this->model,
            'engine_number' => $this->engine_number,
            'chassis_number' => $this->chassis_number,
            'current_mileage' => $this->current_mileage,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'assigned_driver' => $this->whenLoaded('assignedDriver', fn () => [
                'id' => $this->assignedDriver->id,
                'name' => $this->assignedDriver->name,
            ]),
            'secondary_driver' => $this->whenLoaded('secondaryDriver', fn () => $this->secondaryDriver ? [
                'id' => $this->secondaryDriver->id,
                'name' => $this->secondaryDriver->name,
            ] : null),
            // The viewing driver's role ON THIS vehicle — 'primary' or 'secondary'
            // (FR-09, 2026-09). Lets the driver's My Vehicle screen label each
            // vehicle so a two-driver crew is never left guessing which is which.
            // Present only for a driver viewer; absent for the admin vehicle list.
            'my_role' => $this->when(
                (bool) $request->user()?->isDriver(),
                fn () => $this->assigned_driver_id === $request->user()->id
                    ? 'primary'
                    : ($this->secondary_driver_id === $request->user()->id ? 'secondary' : null)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

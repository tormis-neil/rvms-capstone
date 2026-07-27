<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Dispatch
 */
class DispatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'mission_type' => $this->mission_type,
            'mission_other' => $this->mission_other,
            'location' => $this->location,
            'time_out' => $this->time_out,
            'odometer_out' => $this->odometer_out,
            'time_in' => $this->time_in,
            'odometer_in' => $this->odometer_in,
            'return_status' => $this->return_status,
            'remarks' => $this->remarks,
            'is_active' => $this->isActive(),
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'plate_number' => $this->vehicle->plate_number,
                'type' => $this->vehicle->type,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => [
                'id' => $this->driver->id,
                'name' => $this->driver->name,
            ]),
        ];
    }
}

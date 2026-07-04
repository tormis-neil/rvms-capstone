<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Vehicle
 */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'assigned_driver_id' => $this->assigned_driver_id,
            'type' => $this->type,
            'plate_number' => $this->plate_number,
            'make' => $this->make,
            'model' => $this->model,
            'engine_number' => $this->engine_number,
            'chassis_number' => $this->chassis_number,
            'current_mileage' => $this->current_mileage,
            'status' => $this->status,
            'assigned_driver' => new UserResource($this->whenLoaded('assignedDriver')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

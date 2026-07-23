<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RepairLog
 */
class RepairLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'repair_date' => $this->repair_date?->toDateString(),
            'scope_of_work' => $this->scope_of_work,
            'parts_replaced' => $this->parts_replaced,
            'cost' => $this->cost,
            'repair_source' => $this->repair_source,
            'external_shop_name' => $this->external_shop_name,
            'remarks' => $this->remarks,
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'plate_number' => $this->vehicle->plate_number,
                'type' => $this->vehicle->type,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => $this->driver ? [
                'id' => $this->driver->id,
                'name' => $this->driver->name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

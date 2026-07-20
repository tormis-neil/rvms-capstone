<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Inspection
 */
class InspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'inspection_date' => $this->inspection_date?->toDateString(),
            'review_status' => $this->review_status,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'submitted_at' => $this->created_at,
            'result' => $this->whenLoaded('items', fn () => $this->resultLabel()),
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'plate_number' => $this->vehicle->plate_number,
                'type' => $this->vehicle->type,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => [
                'id' => $this->driver->id,
                'name' => $this->driver->name,
            ]),
            'items' => InspectionItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

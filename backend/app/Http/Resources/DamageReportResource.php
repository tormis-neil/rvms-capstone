<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\DamageReport
 */
class DamageReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'nature_of_damage' => $this->nature_of_damage,
            'suspected_parts' => $this->suspected_parts,
            'photo_path' => $this->photo_path,
            'photo_url' => $this->photo_path ? Storage::url($this->photo_path) : null,
            'date_reported' => $this->date_reported?->toDateString(),
            'status' => $this->status,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'submitted_at' => $this->created_at,
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

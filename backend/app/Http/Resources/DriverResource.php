<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'license_number' => $this->license_number,
            'license_expiry_date' => $this->license_expiry_date?->toDateString(),
            'license_status' => $this->licenseStatus(),
            'vehicles' => $this->whenLoaded('vehicles', fn () => $this->vehicles->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'type' => $v->type,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}

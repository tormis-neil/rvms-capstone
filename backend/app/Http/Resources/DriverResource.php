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
            // TESDA NC II (Driving) — monitored exactly like the licence (FR-08, 2026-09).
            'nc_ii_number' => $this->nc_ii_number,
            'nc_ii_expiry_date' => $this->nc_ii_expiry_date?->toDateString(),
            'nc_ii_status' => $this->ncIiStatus(),
            'vehicles' => $this->whenLoaded('vehicles', fn () => $this->vehicles->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'type' => $v->type,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'role' => $this->role,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'license_number' => $this->license_number,
            'license_expiry_date' => $this->license_expiry_date?->toDateString(),
            // TESDA NC II (Driving) — carried on /me so the driver's own app can
            // show an NC II status card beside the licence one (FR-08, 2026-09).
            'nc_ii_number' => $this->nc_ii_number,
            'nc_ii_expiry_date' => $this->nc_ii_expiry_date?->toDateString(),
            'agency' => new AgencyResource($this->whenLoaded('agency')),
        ];
    }
}
